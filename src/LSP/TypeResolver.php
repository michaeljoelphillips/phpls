<?php

declare(strict_types=1);

namespace LanguageServer\LSP;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TypeResolver
{
    private $reflector;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function getType(ParsedDocument $document, $node): ?string
    {
        if ($node instanceof Variable) {
            return $this->getVariableType($document, $node);
        }

        if ($node instanceof MethodCall) {
            if ($node->var instanceof MethodCall) {
                return $this->getReturnType($document, $node->var);
            }

            return $this->getType($document, $node->var);
        }

        if ($node instanceof StaticCall) {
            return $this->getType($document, $node->class);
        }

        if ($node instanceof New_) {
            return $this->getType($document, $node->class);
        }

        if ($node instanceof Assign && $node->expr instanceof New_) {
            return $this->getNewAssignmentType($document, $node->expr);
        }

        if ($node instanceof Param) {
            return $this->getArgumentType($document, $node);
        }

        if ($node instanceof PropertyFetch) {
            if ($node->var instanceof MethodCall) {
                return $this->getReturnType($document, $node->var);
            }

            return $this->getPropertyType($document, $node);
        }

        if ($node instanceof Name) {
            return $this->getTypeFromClassReference($document, $node);
        }

        return null;
    }

    private function getReturnType(ParsedDocument $document, MethodCall $methodCall): string
    {
        $variableType = $this->getType($document, $methodCall);
        $methodName = $methodCall->name;

        $reflectedClass = $this->reflector->reflect($variableType);
        $reflectedMethod = $reflectedClass->getMethod($methodName->name);

        return (string) $reflectedMethod->getReturnType();
    }

    /**
     * Attempt to find the variable type.
     *
     * If $node is an instance variable, the document classname will be
     * returned.  Otherwise, the closest assignment will be used to resolve the
     * type.
     *
     * @param ParsedDocument $document
     * @param Variable       $variable
     *
     * @return string
     */
    public function getVariableType(ParsedDocument $document, Variable $variable): string
    {
        if ('this' === $variable->name) {
            return $document->getClassName();
        }

        $closestVariable = $this->findClosestVariableReferencesInDocument($variable, $document);

        return $this->getType($document, $closestVariable);
    }

    private function findClosestVariableReferencesInDocument(Variable $variable, ParsedDocument $document): NodeAbstract
    {
        $expressions = $this->findVariableReferencesInDocument($variable, $document);
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
        usort($expressions, function (Expr $a, Expr $b) {
            return $a->getEndFilePos() <=> $b->getEndFilePos();
        });

        return $expressions;
    }

    /**
     * Get the type for the class specified by a new operator.
     *
     * @param ParsedDocument $document
     * @param New_           $node
     *
     * @return string
     */
    private function getNewAssignmentType(ParsedDocument $document, New_ $node): string
    {
        return $this->getType($document, $node->class);
    }

    /**
     * Get the type of a function parameter.
     *
     * @param ParsedDocument $document
     * @param Param          $param
     */
    private function getArgumentType(ParsedDocument $document, Param $param)
    {
        return $this->getType($document, $param->type);
    }

    /**
     * Get the type of a class reference.
     *
     * @param ParsedDocument $document
     * @param Name           $node
     */
    private function getTypeFromClassReference(ParsedDocument $document, Name $node)
    {
        // @todo: Account for FQCN
        $className = $node->parts[0];

        $useStatements = $document->getUseStatements();

        $fqcn = array_filter(
            $useStatements,
            function (Use_ $use) use ($className) {
                $shortClassName = end($use->uses[0]->name->parts);

                return $shortClassName === $className;
            }
        );

        if (empty($fqcn)) {
            return sprintf('%s\%s', $document->getNamespace(), $className);
        }

        return (string) array_pop($fqcn)->uses[0]->name;
    }

    /**
     * @param ParsedDocument $document
     * @param Name           $node
     */
    private function getPropertyType(ParsedDocument $document, PropertyFetch $property)
    {
        $constructor = $document->getConstructorNode();

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
