<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use LanguageServer\TextDocument;
use PhpParser\Parser;

class DocumentParser
{
    private Parser $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function parse(TextDocument $document) : ParsedDocument
    {
        $nodes = $this->parser->parse($document->getSource());

        return new ParsedDocument($nodes, $document);
    }
}
