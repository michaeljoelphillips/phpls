<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ClassConstantProvider implements CompletionProviderInterface
{
    protected function complete(Expr $expression, ReflectionClass $reflection): array
    {
        return array_values(array_map(
            function (ReflectionClassConstant $constant) {
                return new CompletionItem(
                    $constant->getName(),
                    CompletionItemKind::VALUE,
                    null,
                    $constant->getDocComment()
                );
            },
            $reflection->getReflectionConstants()
        ));
    }

    public function supports(Expr $expression): bool
    {
        return $expression instanceof ClassConstFetch;
    }
}
