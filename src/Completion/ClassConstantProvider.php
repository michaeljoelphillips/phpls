<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

use function array_map;
use function array_values;

class ClassConstantProvider implements CompletionProvider
{
    /**
     * {@inheritdoc}
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array
    {
        return array_values(array_map(
            static function (ReflectionClassConstant $constant) {
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
        return $expression instanceof ClassConstFetch;
    }
}
