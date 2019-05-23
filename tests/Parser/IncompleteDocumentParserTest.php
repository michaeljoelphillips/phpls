<?php

declare(strict_types=1);

namespace LanguageServer\Test\Parser;

use LanguageServer\Parser\IncompleteDocumentParser;
use LanguageServer\TextDocument;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class IncompleteDocumentParserTest extends TestCase
{
    private $subject;

    public function setUp(): void
    {
        $this->subject = new IncompleteDocumentParser(
            (new ParserFactory())->create(ParserFactory::PREFER_PHP7)
        );
    }

    /**
     * @dataProvider incompleteSyntax
     */
    public function testParseFixesIncompleteSyntax(string $fixtureName): void
    {
        $document = $this->readDocument($fixtureName);

        $this->subject->parse($document);
        $this->addToAssertionCount(1);
    }

    private function readDocument(string $fixtureName): TextDocument
    {
        $documentSource = file_get_contents(__DIR__.'/../fixtures/IncompleteSyntax/'.$fixtureName);

        return new TextDocument('file:///tmp/Foo.php', $documentSource, 0);
    }

    public function incompleteSyntax(): array
    {
        return [
            ['IncompletePropertyAccessFollowedByIfStatement.php'],
            ['IncompletePropertyAccessFollowedByTryCatch.php'],
            ['IncompletePropertyAccessFollowedByReturn.php'],
            ['IncompletePropertyAccessWithinMethodCall.php'],
            ['IncompleteStaticAccessFollowedByReturn.php'],
            ['IncompleteStaticAccessWithinMethodCall.php'],
        ];
    }
}
