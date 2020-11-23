<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use LanguageServer\ParsedDocument;
use LanguageServerProtocol\CompletionItem;
use PhpParser\NodeAbstract;

interface DocumentBasedCompletionProvider extends CompletionProvider
{
    /**
     * @return CompletionItem[]
     */
    public function complete(NodeAbstract $expression, ParsedDocument $document): array;
}
