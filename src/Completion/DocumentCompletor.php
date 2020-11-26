<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use LanguageServer\ParsedDocument;
use LanguageServerProtocol\CompletionItem;
use PhpParser\Node;

interface DocumentCompletor
{
    /**
     * @return array<int, CompletionItem>
     */
    public function complete(Node $expression, ParsedDocument $document): array;

    public function supports(Node $node): bool;
}
