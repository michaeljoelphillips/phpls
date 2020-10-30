<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

use function assert;

class InstanceMethodProvider extends MethodProvider
{
    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof PropertyFetch;
    }

    protected function filterMethod(NodeAbstract $expression, ReflectionClass $class, ReflectionMethod $method): bool
    {
        assert($expression instanceof PropertyFetch);
        assert($expression->var instanceof Variable);

        if ($method->isStatic() === true) {
            return false;
        }

        if ($method->isPublic() === true) {
            return true;
        }

        if ($expression->var->name === 'this') {
            if ($method->isPrivate() === true) {
                return $method->getDeclaringClass() === $class;
            }

            return true;
        }

        return false;
    }
}
