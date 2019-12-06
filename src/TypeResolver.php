<?php

declare(strict_types=1);

namespace LanguageServer;

use LanguageServer\Parser\ParsedDocument;
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
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TypeResolver
{
    private Reflector $reflector;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function getType(ParsedDocument $document, $node): ?string
    {
        if ($node instanceof Variable) {
            return $this->getVariableType($document, $node);
        }

        if (
            $node instanceof StaticPropertyFetch ||
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

        if ($node instanceof Assign && $node->expr instanceof New_) {
            return $this->getNewAssignmentType($document, $node->expr);
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

    private function getReturnType(ParsedDocument $document, MethodCall $methodCall): ?string
    {
        $variableType = $this->getType($document, $methodCall);

        if (null === $variableType) {
            return null;
        }

        $methodName = $methodCall->name;

        $reflectedClass = $this->reflector->reflect($variableType);
        $reflectedMethod = $reflectedClass->getMethod($methodName->name);

        $type = (string) $reflectedMethod->getReturnType();

        if ('' === $type) {
            return null;
        }

        return $type;
    }

    /**
     * Attempt to find the variable type.
     *
     * If $node is an instance variable, the document classname will be
     * returned.  Otherwise, the closest assignment will be used to resolve the
     * type.
     *
     * @return string
     */
    public function getVariableType(ParsedDocument $document, Variable $variable): ?string
    {
        if ('this' === $variable->name) {
            return $document->getClassName();
        }

        $closestVariable = $this->findClosestVariableReferencesInDocument($variable, $document);

        if (null === $closestVariable) {
            return null;
        }

        return $this->getType($document, $closestVariable);
    }

    private function findClosestVariableReferencesInDocument(Variable $variable, ParsedDocument $document): ?NodeAbstract
    {
        $expressions = $this->findVariableReferencesInDocument($variable, $document);

        if (empty($expressions)) {
            return null;
        }

        $orderedExpressions = $this->sortNodesByEndingLocation($expressions);

        return end($orderedExpressions);
    }

    private function findVariableReferencesInDocument(Variable $variable, ParsedDocument $document): array
    {
        return $document->searchNodes(
            function (NodeAbstract $node) use ($variable) {
                return ($node instanceof Assign || $node instanceof Param)
                    && $node->var->name === $variable->name
                    && $node->getEndFilePos() < $variable->getEndFilePos();
            }
        );
    }

    private function sortNodesByEndingLocation(array $expressions): array
    {
        usort($expressions, function (NodeAbstract $a, NodeAbstract $b) {
            return $a->getEndFilePos() <=> $b->getEndFilePos();
        });

        return $expressions;
    }

    /**
     * Get the type for the class specified by a new operator.
     */
    private function getNewAssignmentType(ParsedDocument $document, New_ $node): string
    {
        return $this->getType($document, $node->class);
    }

    /**
     * Get the type of a function parameter.
     */
    private function getArgumentType(ParsedDocument $document, Param $param)
    {
        return $this->getType($document, $param->type);
    }

    /**
     * Get the type of a class reference.
     */
    private function getTypeFromClassReference(ParsedDocument $document, Name $node)
    {
        if ('self' === (string) $node) {
            return $document->getClassName();
        }

        $useStatements = array_merge(...array_column($document->getUseStatements(), 'uses'));

        $matchingUseStatement = array_filter(
            $useStatements,
            function (UseUse $use) use ($node) {
                if (null !== $use->alias && $use->alias->name === $node->getLast()) {
                    return true;
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

    private function getPropertyType(ParsedDocument $document, PropertyFetch $property): ?string
    {
        if ('this' === $property->var->name) {
            $propertyDeclaration = $document->getClassProperty((string) $property->name);

            if (null !== $propertyDeclaration && $this->propertyHasResolvableType($propertyDeclaration)) {
                return $this->getType($document, $propertyDeclaration->type);
            }

            return $this->getPropertyTypeFromConstructorAssignment($document, $property);
        }

        return $this->getPropertyTypeFromDocblock($document, $property);
    }

    private function propertyHasResolvableType(Property $property): bool
    {
        return $property->type instanceof Identifier
            || $property->type instanceof Name;
    }

    private function getPropertyTypeFromDocblock(ParsedDocument $document, PropertyFetch $property): ?string
    {
        $propertyName = $property->name;
        $variableType = $this->getType($document, $property->var);

        if (null === $variableType) {
            return null;
        }

        $reflectedClass = $this->reflector->reflect($variableType);
        $reflectedProperty = $reflectedClass->getProperty($propertyName->name);

        if (null === $reflectedProperty) {
            return null;
        }

        $docblockTypes = $reflectedProperty->getDocBlockTypeStrings();

        if (empty($docblockTypes)) {
            return null;
        }

        // @todo: Figure out what to do with union types
        return $this->getType($document, new Name(array_pop($docblockTypes)));
    }

    private function getPropertyTypeFromConstructorAssignment(ParsedDocument $document, PropertyFetch $property): ?string
    {
        $constructor = $document->getConstructorNode();

        if (null === $constructor) {
            return null;
        }

        $propertyAssignment = array_values(array_filter(
            $constructor->stmts,
            function (NodeAbstract $node) use ($property) {
                return $node instanceof Expression
                    && $node->expr->var->name->name === $property->name->name;
            }
        ));

        if (empty($propertyAssignment)) {
            return null;
        }

        return $this->getType($document, $propertyAssignment[0]->expr->expr);
    }
}
