<?php

declare(strict_types=1);

namespace LanguageServer\Inference;

use LanguageServer\ParsedDocument;
use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\Reflector;
use function array_column;
use function array_filter;
use function array_merge;
use function array_pop;
use function array_values;
use function end;
use function implode;
use function in_array;
use function sprintf;
use function usort;

class TypeResolver
{
    private Reflector $reflector;
    private DocBlockFactory $docblockFactory;

    public function __construct(Reflector $reflector)
    {
        $this->reflector       = $reflector;
        $this->docblockFactory = DocBlockFactory::createInstance();
    }

    public function getType(ParsedDocument $document, ?NodeAbstract $node) : ?string
    {
        if ($node instanceof Variable) {
            return $this->getVariableType($document, $node);
        }

        if ($node instanceof StaticPropertyFetch ||
            $node instanceof ClassConstFetch
        ) {
            return $this->getType($document, $node->class);
        }

        if ($node instanceof StaticCall) {
            return $this->getType($document, $node->class);
        }

        if ($node instanceof New_) {
            return $this->getType($document, $node->class);
        }

        if ($node instanceof Name) {
            return $this->getTypeFromClassReference($document, $node);
        }

        if ($node instanceof Assign) {
            if ($node->expr instanceof New_) {
                return $this->getNewAssignmentType($document, $node->expr);
            }

            if ($node->expr instanceof MethodCall) {
                return $this->getReturnType($document, $node->expr);
            }

            return $this->getType($document, $node->expr);
        }

        if ($node instanceof Param) {
            return $this->getArgumentType($document, $node);
        }

        if ($node instanceof MethodCall) {
            if ($node->var instanceof MethodCall) {
                return $this->getReturnType($document, $node->var);
            }

            if ($node->var instanceof PropertyFetch) {
                return $this->getPropertyType($document, $node->var);
            }

            return $this->getType($document, $node->var);
        }

        if ($node instanceof PropertyFetch) {
            if ($node->var instanceof MethodCall) {
                return $this->getReturnType($document, $node->var);
            }

            if ($node->var instanceof PropertyFetch) {
                return $this->getPropertyType($document, $node->var);
            }

            return $this->getType($document, $node->var);
        }

        return null;
    }

    private function getReturnType(ParsedDocument $document, MethodCall $methodCall) : ?string
    {
        $variableType = $this->getType($document, $methodCall);

        if ($variableType === null) {
            return null;
        }

        $reflectedClass  = $this->reflector->reflect($variableType);
        $reflectedMethod = $reflectedClass->getMethod($methodCall->name->name);

        if ($reflectedMethod->hasReturnType()) {
            $returnType = (string) $reflectedMethod->getReturnType();
        } else {
            $returnType = implode('|', $reflectedMethod->getDocBlockReturnTypes());
        }

        if ($returnType === '') {
            return null;
        }

        if (in_array($returnType, ['self', '$this', 'this', 'static'])) {
            return $reflectedClass->getName();
        }

        if ($returnType === 'parent') {
            $parent = $reflectedClass->getParentClass();

            if ($parent !== false) {
                return $parent->getName();
            }
        }

        return $returnType;
    }

    /**
     * Attempt to find the variable type.
     *
     * If $node is an instance variable, the document classname will be
     * returned.  Otherwise, the closest assignment will be used to resolve the
     * type.
     */
    public function getVariableType(ParsedDocument $document, Variable $variable) : ?string
    {
        if ($variable->name === 'this') {
            return $document->getClassName();
        }

        $closestVariable = $this->findClosestVariableReferencesInDocument($variable, $document);

        if ($closestVariable === null) {
            return null;
        }

        return $this->getType($document, $closestVariable);
    }

    private function findClosestVariableReferencesInDocument(Variable $variable, ParsedDocument $document) : ?NodeAbstract
    {
        $expressions = $this->findVariableReferencesInDocument($variable, $document);

        if (empty($expressions)) {
            return null;
        }

        $orderedExpressions = $this->sortNodesByEndingLocation($expressions);

        return end($orderedExpressions);
    }

    /**
     * @return NodeAbstract[]
     */
    private function findVariableReferencesInDocument(Variable $variable, ParsedDocument $document) : array
    {
        return $document->searchNodes(
            static function (NodeAbstract $node) use ($variable) {
                return ($node instanceof Assign || $node instanceof Param)
                    && $node->var->name === $variable->name
                    && $node->getEndFilePos() < $variable->getEndFilePos();
            }
        );
    }

    /**
     * @param NodeAbstract[] $expressions
     *
     * @return NodeAbstract[]
     */
    private function sortNodesByEndingLocation(array $expressions) : array
    {
        usort($expressions, static function (NodeAbstract $a, NodeAbstract $b) {
            return $a->getEndFilePos() <=> $b->getEndFilePos();
        });

        return $expressions;
    }

    /**
     * Get the type for the class specified by a new operator.
     */
    private function getNewAssignmentType(ParsedDocument $document, New_ $node) : string
    {
        return $this->getType($document, $node->class);
    }

    private function getArgumentType(ParsedDocument $document, Param $param) : ?string
    {
        return $this->getType($document, $param->type);
    }

    private function getTypeFromClassReference(ParsedDocument $document, Name $node) : ?string
    {
        if ((string) $node === 'self') {
            return $document->getClassName();
        }

        $useStatements = array_merge(...array_column($document->getUseStatements(), 'uses'));

        $matchingUseStatement = array_filter(
            $useStatements,
            static function (UseUse $use) use ($node) {
                if ($use->alias !== null) {
                    return $use->alias->name === $node->getLast();
                }

                return $use->name->getLast() === $node->getLast();
            }
        );

        if (empty($matchingUseStatement)) {
            if ($node->isUnqualified()) {
                return sprintf('%s\%s', $document->getNamespace(), $node->getLast());
            }

            // If the node is qualified, return it.
            return $node->toCodeString();
        }

        return array_pop($matchingUseStatement)->name->toCodeString();
    }

    private function getPropertyType(ParsedDocument $document, PropertyFetch $property) : ?string
    {
        $propertyDeclaration = $document->getClassProperty((string) $property->name);

        if ($propertyDeclaration !== null && $this->propertyHasResolvableType($propertyDeclaration)) {
            return $this->getType($document, $propertyDeclaration->type);
        }

        $docblockType = $this->getPropertyTypeFromDocblock($document, $property);

        if ($docblockType !== null) {
            return $docblockType;
        }

        return $this->getPropertyTypeFromConstructorAssignment($document, $property);
    }

    private function propertyHasResolvableType(Property $property) : bool
    {
        return $property->type instanceof Identifier
            || $property->type instanceof Name;
    }

    private function getPropertyTypeFromDocblock(ParsedDocument $document, PropertyFetch $property) : ?string
    {
        $propertyName = $property->name;
        $variableType = $this->getType($document, $property);

        if ($variableType === null) {
            return null;
        }

        $reflectedClass    = $this->reflector->reflect($variableType);
        $reflectedProperty = $reflectedClass->getProperty($propertyName->name);

        if ($reflectedProperty === null) {
            return $this->getPropertyTypeFromClassDocblock($document, $property, $reflectedClass);
        }

        return $this->getPropertyFromPropertyDocblock($document, $reflectedProperty);
    }

    private function getPropertyTypeFromClassDocblock(ParsedDocument $document, PropertyFetch $property, ReflectionClass $class) : ?string
    {
        $propertyTags = $this->docblockFactory->create($class->getDocComment())->getTagsByName('property');

        if (empty($propertyTags) === true) {
            return null;
        }

        foreach ($propertyTags as $propertyTag) {
            if ($propertyTag->getVariableName() === $property->name->name) {
                return (string) $propertyTag->getType();
            }
        }

        return null;
    }

    private function getPropertyFromPropertyDocblock(ParsedDocument $document, ReflectionProperty $reflectedProperty) : ?string
    {
        $docblockTypes = $reflectedProperty->getDocBlockTypeStrings();

        if (empty($docblockTypes) === true) {
            return null;
        }

        // @todo: Figure out what to do with union types
        return $this->getType($document, new Name(array_pop($docblockTypes)));
    }

    private function getPropertyTypeFromConstructorAssignment(ParsedDocument $document, PropertyFetch $property) : ?string
    {
        $constructor = $document->getConstructorNode();

        if ($constructor === null) {
            return null;
        }

        $propertyAssignment = array_values(array_filter(
            $constructor->stmts,
            static function (NodeAbstract $node) use ($property) {
                return $node instanceof Expression
                    && $node->expr instanceof Assign
                    && $node->expr->var->name->name === $property->name->name;
            }
        ));

        if (empty($propertyAssignment)) {
            return null;
        }

        return $this->getType($document, $propertyAssignment[0]->expr->expr);
    }
}
