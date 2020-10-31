<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

use function assert;
use function is_string;
use function property_exists;

class InstanceMethodProvider extends MethodProvider
{
    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof PropertyFetch;
    }

    protected function filterMethod(NodeAbstract $expression, ReflectionClass $class, ReflectionMethod $method): bool
    {
        if ($method->isStatic() === true) {
            return false;
        }

        if ($method->isPublic() === true) {
            return true;
        }

        assert(property_exists($expression, 'var'));
        assert($expression->var->name instanceof Identifier || is_string($expression->var->name));

        if ((string) $expression->var->name === 'this') {
            if ($method->isPrivate() === true) {
                return $method->getDeclaringClass() === $class;
            }

            return true;
        }

        return false;
    }
}
