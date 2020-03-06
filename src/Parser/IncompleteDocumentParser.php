<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use LanguageServer\TextDocument;
use PhpParser\Parser;
use function preg_replace;
use function preg_replace_callback;
use function sprintf;
use const PHP_EOL;

class IncompleteDocumentParser implements DocumentParser
{
    private Parser $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function parse(TextDocument $document) : ParsedDocument
    {
        $documentSource = $this->amendDocumentSource($document->getSource());

        $nodes = $this->parser->parse($documentSource);

        return new ParsedDocument($nodes, $document);
    }

    private function amendDocumentSource(string $source) : string
    {
        $source = $this->stubIncompleteAccessors($source);
        $source = $this->stubIncompleteStaticAccessors($source);

        return $source;
    }

    private function stubIncompleteAccessors(string $source) : string
    {
        return preg_replace_callback(
            '/((\()*\$(\w+(\([$\w]*\))?->\w*)*)\n/',
            static function (array $matches) {
                $result = sprintf('%slspSyntaxStub', $matches[1]);

                if (isset($matches[2]) && $matches[2] === '(') {
                    $result .= ')';
                }

                return $result .= ';' . PHP_EOL;
            },
            $source
        );
    }

    private function stubIncompleteStaticAccessors(string $source) : string
    {
        return preg_replace('/(\w+::)\n/', '${1}LSP_SYNTAX_STUB;' . PHP_EOL, $source);
    }
}
