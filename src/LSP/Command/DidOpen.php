<?php

declare(strict_types=1);

namespace LanguageServer\LSP\Command;

use LanguageServer\LSP\DocumentParserInterface;
use LanguageServer\LSP\TextDocument;
use LanguageServer\LSP\TextDocumentRegistry;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DidOpen
{
    private $registry;

    public function __construct(TextDocumentRegistry $registry, DocumentParserInterface $parser)
    {
        $this->registry = $registry;
        $this->parser = $parser;
    }

    public function __invoke(array $params): void
    {
        $textDocument = new TextDocument(
            $params['textDocument']['uri'],
            $params['textDocument']['text'],
            $params['textDocument']['version'],
        );

        $this->parser->parse($textDocument);

        $this->registry->add($textDocument);
    }
}
