<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use PhpParser\NodeAbstract;
use PhpParser\Node\Expr\ClassConstFetch;
use Roave\BetterReflection\Reflection\ReflectionMethod;

class StaticMethodProvider extends MethodProvider
{
    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof ClassConstFetch;
    }

    protected function filterMethod(ReflectionMethod $method): bool
    {
        return $method->isStatic();
    }
}
