<?php

declare(strict_types=1);

namespace LanguageServer\LSP;

use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Use_;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TypeResolver
{
    public function getType(ParsedDocument $document, $node): string
    {
        if ($node instanceof MethodCall) {
            return $this->getVariableType($document, $node->var);
        }

        if ($node instanceof Assign && $node->expr instanceof New_) {
            return $this->getNewAssignmentType($document, $node->expr);
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
     * @param Variable       $node
     *
     * @return string
     */
    public function getVariableType(ParsedDocument $document, Variable $node): string
    {
        if ('this' === $node->name) {
            return $document->getClassName();
        }

        $assignments = array_filter(
            $document->findNodes(Assign::class),
            function (Assign $assignment) use ($node) {
                return $assignment->var->name === $node->name
                    && $assignment->getEndFilePos() < $node->getEndFilePos();
            }
        );

        usort(
            $assignments,
            function (Assign $a, Assign $b) {
                return $a->getEndFilePos() <=> $b->getEndFilePos();
            }
        );

        $closestAssignmentToVariable = end($assignments);

        return $this->getType($document, $closestAssignmentToVariable);
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
        $className = $node->class->parts[0];

        $useStatements = $document->getUseStatements();

        $fqcn = array_filter(
            $useStatements,
            function (Use_ $use) use ($className) {
                return end($use->uses[0]->name->parts) === $className;
            }
        );

        if (false === empty($fqcn)) {
            return (string) array_pop($fqcn)->uses[0]->name;
        }

        return sprintf('%s\%s', $document->getNamespace(), $className);
    }
}
