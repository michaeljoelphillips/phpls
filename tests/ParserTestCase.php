<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\Parser\IncompleteDocumentParser;
use LanguageServer\Parser\ParsedDocument;
use LanguageServer\TextDocument;
use PhpParser\Lexer;
use PhpParser\ParserFactory;

abstract class ParserTestCase extends FixtureTestCase
{
    protected function getParser() : IncompleteDocumentParser
    {
        return new IncompleteDocumentParser(
            (new ParserFactory())->create(
                ParserFactory::PREFER_PHP7,
                new Lexer([
                    'usedAttributes' => [
                        'comments',
                        'startLine',
                        'endLine',
                        'startFilePos',
                        'endFilePos',
                    ],
                ])
            )
        );
    }

    protected function parse(string $file) : ParsedDocument
    {
        $parser = $this->getParser();

        $document = new TextDocument($file, $this->loadFixture($file), 0);

        return $parser->parse($document);
    }
}
