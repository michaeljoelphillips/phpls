<?php

declare(strict_types=1);

namespace LanguageServer\Completion\Completors;

use LanguageServer\Completion\ReflectionCompletor;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;

use function array_filter;
use function array_map;
use function array_values;
use function assert;
use function implode;

class StaticPropertyCompletor implements ReflectionCompletor
{
    /**
     * {@inheritdoc}
     */
    public function complete(Node $expression, ReflectionClass $reflection): array
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

    protected function filterMethod(Node $expression, ReflectionClass $class, ReflectionProperty $property): bool
    {
        if ($property->isPublic()) {
            return true;
        }

        assert($expression instanceof ClassConstFetch);
        assert($expression->class instanceof Name);
        $className = $expression->class->getLast();

        if ($className === 'self') {
            if ($property->isPrivate() === true) {
                return $property->getDeclaringClass() === $class;
            }

            return true;
        }

        if ($className === 'parent') {
            return $property->isProtected();
        }

        return false;
    }

    public function supports(Node $expression): bool
    {
        return $expression instanceof ClassConstFetch;
    }
}
