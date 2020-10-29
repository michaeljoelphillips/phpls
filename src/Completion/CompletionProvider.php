<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use LanguageServerProtocol\CompletionItem;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;

interface CompletionProvider
{
    /**
     * @return CompletionItem[]
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array;

    public function supports(NodeAbstract $expression): bool;
}
