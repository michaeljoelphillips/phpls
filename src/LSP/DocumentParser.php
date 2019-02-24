<?php

declare(strict_types=1);

namespace LanguageServer\LSP;

use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DocumentParser
{
    private const LEXER_OPTIONS = [
        'usedAttributes' => [
            'comments',
            'startLine',
            'endLine',
            'startFilePos',
            'endFilePos',
        ],
    ];

    /** @var Parser */
    private $parser;

    public function __construct()
    {
        $factory = new ParserFactory();

        $this->parser = $factory->create(
            ParserFactory::ONLY_PHP7,
            new Lexer(self::LEXER_OPTIONS)
        );
    }

    public function parse(TextDocument $document): ParsedDocument
    {
        $nodes = $this->parser->parse($document->getSource());

        return new ParsedDocument($nodes ?? [], $document);
    }
}
