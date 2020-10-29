<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\NodeAbstract;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;

use function array_filter;
use function array_map;
use function array_values;
use function implode;

class StaticPropertyProvider implements CompletionProvider
{
    /**
     * {@inheritdoc}
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array
    {
        $properties = array_filter(
            $reflection->getProperties(CoreReflectionProperty::IS_STATIC),
            // phpcs:ignore
            fn (ReflectionProperty $property) => $this->filterMethod($expression, $reflection, $property)
        );

        return array_values(array_map(
            static function (ReflectionProperty $property) {
                return new CompletionItem(
                    $property->getName(),
                    CompletionItemKind::PROPERTY,
                    implode('|', $property->getDocblockTypeStrings()),
                    $property->getDocComment()
                );
            },
            $properties
        ));
    }

    protected function filterMethod(NodeAbstract $expression, ReflectionClass $class, ReflectionProperty $property): bool
    {
        if ($property->isPublic()) {
            return true;
        }

        if ($expression->class->name->name === 'self') {
            if ($property->isPrivate() === true) {
                return $property->getDeclaringClass() === $class;
            }

            return true;
        }

        if ($expression->class->name->name === 'parent') {
            return $property->isProtected();
        }

        return false;
    }

    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof ClassConstFetch;
    }
}
