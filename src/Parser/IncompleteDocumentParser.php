<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use LanguageServer\TextDocument;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Parser;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class IncompleteDocumentParser implements DocumentParserInterface
{
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function parse(TextDocument $document): ParsedDocument
    {
        $completedSourceCode = $this->correctIncompleteSyntax($document);
        $nodes = $this->parser->parse($completedSourceCode, new Collecting());

        return new ParsedDocument($nodes, $document);
    }

    private function correctIncompleteSyntax(TextDocument $document): string
    {
        $source = $document->getSource();
        $source = preg_replace('/(\$\w+->)\n/i', '${1}lspSyntaxStub;'.PHP_EOL, $source);
        $source = preg_replace('/(\w+::)\n/i', '${1}LSP_SYNTAX_STUB;'.PHP_EOL, $source);

        return $source;
    }
}
