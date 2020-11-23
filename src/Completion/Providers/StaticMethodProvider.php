<?php

declare(strict_types=1);

namespace LanguageServer\Completion\Providers;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

use function assert;

class StaticMethodProvider extends MethodProvider
{
    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof ClassConstFetch;
    }

    protected function filterMethod(NodeAbstract $expression, ReflectionClass $class, ReflectionMethod $method): bool
    {
        if ($method->isStatic() === false) {
            return false;
        }

        if ($method->isPublic()) {
            return true;
        }

        assert($expression instanceof ClassConstFetch);
        assert($expression->class instanceof Name);
        $className = $expression->class->getLast();

        if ($className === 'self') {
            if ($method->isPrivate() === true) {
                return $method->getDeclaringClass() === $class;
            }

            return true;
        }

        if ($className === 'parent') {
            return $method->isProtected();
        }

        return false;
    }
}
