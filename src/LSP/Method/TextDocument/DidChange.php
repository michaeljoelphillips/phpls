<?php

declare(strict_types=1);

namespace LanguageServer\LSP\Method\TextDocument;

use LanguageServer\LSP\DocumentParserInterface;
use LanguageServer\LSP\TextDocument;
use LanguageServer\LSP\TextDocumentRegistry;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DidChange
{
    private $registry;

    private $parser;

    public function __construct(TextDocumentRegistry $registry, DocumentParserInterface $parser)
    {
        $this->registry = $registry;
        $this->parser = $parser;
    }

    public function __invoke(array $params): void
    {
        $document = new TextDocument(
            $params['textDocument']['uri'],
            $params['contentChanges'][0]['text'],
            $params['textDocument']['version']
        );

        $this->parser->parse($document);

        $this->registry->add($document);
    }
}
