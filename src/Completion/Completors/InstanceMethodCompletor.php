<?php

declare(strict_types=1);

namespace LanguageServer\Completion\Completors;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

use function assert;
use function is_string;
use function property_exists;

class InstanceMethodCompletor extends MethodCompletor
{
    public function supports(Node $expression): bool
    {
        return $expression instanceof PropertyFetch;
    }

    protected function filterMethod(Node $expression, ReflectionClass $class, ReflectionMethod $method): bool
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
