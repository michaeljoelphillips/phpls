<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use PhpParser\NodeAbstract;
use PhpParser\Node\Expr\ClassConstFetch;

class StaticMethodProvider extends MethodProvider
{
    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof ClassConstFetch;
    }
}
