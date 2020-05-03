<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function array_filter;
use function array_map;
use function array_values;
use function implode;

class InstanceVariableProvider implements CompletionProvider
{
    /**
     * {@inheritdoc}
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection) : array
    {
        $properties = array_filter(
            $reflection->getProperties(),
            // phpcs:ignore
            fn (ReflectionProperty $property) => $this->filterMethod($expression, $reflection, $property)
        );

        return array_values(array_map(
            static function (ReflectionProperty $property) {
                return new CompletionItem(
                    $property->getName(),
                    CompletionItemKind::PROPERTY,
                    $property->hasType() ? (string) $property->getType() : implode('|', $property->getDocblockTypeStrings()),
                    $property->getDocComment()
                );
            },
            $properties
        ));
    }

    private function filterMethod(NodeAbstract $expression, ReflectionClass $class, ReflectionProperty $property) : bool
    {
        if ($property->isPublic() === true) {
            return true;
        }

        if ($expression->var->name === 'this') {
            if ($property->isPrivate() === true) {
                return $property->getDeclaringClass() === $class;
            }

            return true;
        }

        return false;
    }

    public function supports(NodeAbstract $expression) : bool
    {
        return $expression instanceof PropertyFetch && ! $expression->var instanceof MethodCall;
    }
}
