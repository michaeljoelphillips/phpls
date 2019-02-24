<?php

declare(strict_types=1);

namespace LanguageServer\LSP;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;

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
    }

    public function getVariableType(ParsedDocument $document, Variable $node): string
    {
        return $document->getClassName();
    }
}
