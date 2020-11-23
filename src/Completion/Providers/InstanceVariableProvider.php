<?php

declare(strict_types=1);

namespace LanguageServer\Completion\Providers;

use LanguageServer\Completion\TypeBasedCompletionProvider;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Throwable;

use function array_filter;
use function array_map;
use function array_values;
use function assert;
use function implode;

class InstanceVariableProvider implements TypeBasedCompletionProvider
{
    /**
     * {@inheritdoc}
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array
    {
        assert($expression instanceof PropertyFetch);

        $properties = array_filter(
            $reflection->getProperties(),
            // phpcs:ignore
            fn (ReflectionProperty $property) => $this->filterMethod($expression, $reflection, $property)
        );

        return array_values(array_map(
            function (ReflectionProperty $property) {
                return new CompletionItem(
                    $property->getName(),
                    CompletionItemKind::PROPERTY,
                    $this->getReturnTypeString($property),
                    $property->getDocComment()
                );
            },
            $properties
        ));
    }

    private function getReturnTypeString(ReflectionProperty $property): string
    {
        if ($property->hasType()) {
            return (string) $property->getType();
        }

        try {
            $docblockType = $property->getDocBlockTypeStrings();
        } catch (Throwable $e) {
            $docblockType = [];
        }

        return implode('|', $docblockType);
    }

    private function filterMethod(PropertyFetch $expression, ReflectionClass $class, ReflectionProperty $property): bool
    {
        assert($expression->var instanceof Variable);

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

    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof PropertyFetch && $expression->var instanceof Variable;
    }
}
