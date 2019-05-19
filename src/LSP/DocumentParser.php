<?php

declare(strict_types=1);

namespace LanguageServer\LSP;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Parser;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DocumentParser implements DocumentParserInterface
{
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function parse(TextDocument $document): ParsedDocument
    {
        $nodes = $this->parser->parse($document->getSource(), new Collecting());

        return new ParsedDocument($nodes, $document);
    }
}
