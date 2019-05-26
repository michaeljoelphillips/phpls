<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\Parser\DocumentParser;
use LanguageServer\Parser\ParsedDocument;
use LanguageServer\TextDocument;
use PhpParser\Lexer;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
abstract class ParserTestCase extends TestCase
{
    public function getParser()
    {
        return new DocumentParser(
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
            ));
    }

    public function parse(string $file): ParsedDocument
    {
        $parser = $this->getParser();

        $document = new TextDocument($file, $this->loadFixture($file), 0);

        return $parser->parse($document);
    }

    protected function loadFixture(string $fixture)
    {
        return file_get_contents(__DIR__.'/fixtures/'.$fixture);
    }
}
