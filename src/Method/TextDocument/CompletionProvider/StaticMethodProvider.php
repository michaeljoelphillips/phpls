<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\NodeAbstract;
use ReflectionMethod as CoreReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class StaticMethodProvider implements CompletionProviderInterface
{
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array
    {
        return array_values(array_map(
            function (ReflectionMethod $method) {
                return new CompletionItem(
                    $method->getName(),
                    CompletionItemKind::METHOD,
                    (string) $method->getReturnType(),
                    $method->getDocComment()
                );
            },
            $reflection->getMethods(CoreReflectionMethod::IS_STATIC)
        ));
    }

    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof ClassConstFetch;
    }
}
