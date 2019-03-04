<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\LSP\DocumentParser;
use LanguageServer\LSP\ParsedDocument;
use LanguageServer\LSP\TextDocument;
use PhpParser\Lexer;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
abstract class ParserTestCase extends TestCase
{
    public function parse(string $file): ParsedDocument
    {
        $parser = new DocumentParser(
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

        $document = new TextDocument($file, $this->loadFixture(), 0);

        return $parser->parse($document);
    }

    private function loadFixture()
    {
        return file_get_contents(__DIR__.'/fixtures/Foo.php');
    }
}
