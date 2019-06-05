<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\NodeAbstract;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class StaticPropertyProvider implements CompletionProviderInterface
{
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array
    {
        return array_values(array_map(
            function (ReflectionProperty $property) {
                return new CompletionItem(
                    $property->getName(),
                    CompletionItemKind::PROPERTY,
                    implode('|', $property->getDocblockTypeStrings()),
                    $property->getDocComment()
                );
            },
            $reflection->getProperties(CoreReflectionProperty::IS_STATIC)
        ));
    }

    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof ClassConstFetch;
    }
}
