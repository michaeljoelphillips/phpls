<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Method\RemoteMethodInterface;
use LanguageServer\Parser\DocumentParserInterface;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DidChange implements RemoteMethodInterface
{
    private $registry;
    private $parser;

    public function __construct(TextDocumentRegistry $registry, DocumentParserInterface $parser)
    {
        $this->registry = $registry;
        $this->parser = $parser;
    }

    public function __invoke(array $params)
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
