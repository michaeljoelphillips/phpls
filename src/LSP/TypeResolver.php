<?php

declare(strict_types=1);

namespace LanguageServer\LSP;

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

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TypeResolver
{
    public function getType(ParsedDocument $document, $node): string
    {
        if ($node instanceof Variable) {
            return $this->getVariableType($document, $node);
        }

        if ($node instanceof MethodCall) {
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
            return $this->getPropertyType($document, $node);
        }

        if ($node instanceof Name) {
            return $this->getTypeFromClassReference($document, $node);
        }
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

        $closestVariable = $this->findClosestVariableExpressionInDocument($variable, $document);

        return $this->getType($document, $closestVariable);
    }

    private function findClosestVariableExpressionInDocument(Variable $variable, ParsedDocument $document): Expr
    {
        $expressions = $this->findVariableExpressionsInDocument($variable, $document);
        $orderedExpressions = $this->sortNodesByEndingLineNumber($expressions);

        return end($orderedExpressions);
    }

    private function findVariableExpressionsInDocument(Variable $variable, ParsedDocument $document): array
    {
        return $document->searchNodes(
            function (NodeAbstract $node) use ($variable) {
                return ($node instanceof Assign || $node instanceof Param)
                    && $node->var->name === $variable->name
                    && $node->getEndFilePos() < $variable->getEndFilePos();
            }
        );
    }

    private function sortNodesByEndingLineNumber(array $expressions): array
    {
        return usort($expressions, function (Expr $a, Expr $b) {
            return $a->getEndFilePos() <=> $b->getEndFilePos();
        });
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
     * Get the type of a class property via its assignment.
     *
     * @param ParsedDocument $document
     * @param Name           $node
     */
    private function getPropertyType(ParsedDocument $document, PropertyFetch $property)
    {
        $constructor = $document->getConstructorNode();

        // @todo: Handle case where property isn't assigned in constructor.
        $propertyAssignment = array_filter(
            $constructor->stmts,
            function (NodeAbstract $node) use ($property) {
                return $node instanceof Expression
                    && $node->expr->var->name->name === $property->name->name;
            }
        )[0];

        return $this->getType($document, $propertyAssignment->expr->expr);
    }
}
