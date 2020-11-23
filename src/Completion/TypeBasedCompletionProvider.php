<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use LanguageServer\ParsedDocument;
use LanguageServerProtocol\CompletionItem;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;

interface TypeBasedCompletionProvider extends CompletionProvider
{
    /**
     * @return CompletionItem[]
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array;
}
