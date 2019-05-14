<?php

declare(strict_types=1);

namespace LanguageServer\LSP\Command;

use LanguageServer\LSP\TextDocument;
use LanguageServer\LSP\TextDocumentRegistry;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DidChange
{
    private $registry;

    public function __construct(TextDocumentRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function __invoke(array $params): void
    {
        $document = new TextDocument(
            $params['textDocument']['uri'],
            $params['contentChanges'][0]['text'],
            $params['textDocument']['version']
        );

        $this->registry->add($document);
    }
}
