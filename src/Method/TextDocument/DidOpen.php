<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Parser\DocumentParserInterface;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DidOpen
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
        $textDocument = new TextDocument(
            $params['textDocument']['uri'],
            $params['textDocument']['text'],
            $params['textDocument']['version'],
        );

        $this->parser->parse($textDocument);

        $this->registry->add($textDocument);
    }
}
