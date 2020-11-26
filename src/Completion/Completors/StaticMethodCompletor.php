<?php

declare(strict_types=1);

namespace LanguageServer\Completion\Completors;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

use function assert;

class StaticMethodCompletor extends MethodCompletor
{
    public function supports(Node $expression): bool
    {
        return $expression instanceof ClassConstFetch;
    }

    protected function filterMethod(Node $expression, ReflectionClass $class, ReflectionMethod $method): bool
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
