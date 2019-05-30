<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServer\Parser\ParsedDocument;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ClassConstantProvider extends AbstractProvider
{
    protected function mapCompletionItems(ParsedDocument $document, ReflectionClass $reflection): array
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

    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof StaticPropertyFetch
            || $expression instanceof ClassConstFetch;
    }
}