<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

class StaticMethodProvider extends MethodProvider
{
    public function supports(NodeAbstract $expression) : bool
    {
        return $expression instanceof ClassConstFetch;
    }

    protected function filterMethod(NodeAbstract $expression, ReflectionClass $class, ReflectionMethod $method) : bool
    {
        if ($method->isStatic() === false) {
            return false;
        }

        if ($method->isPublic()) {
            return true;
        }

        if ($expression->class->name->name === 'self') {
            if ($method->isPrivate() === true) {
                return $method->getDeclaringClass() === $class;
            }

            return true;
        }

        if ($expression->class->name->name === 'parent') {
            return $method->isProtected();
        }

        return false;
    }
}
