<?php

declare(strict_types=1);

namespace Test\LSP;

use LanguageServer\LSP\DocumentParser;
use LanguageServer\LSP\TextDocument;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ParsedDocumentTest extends TestCase
{
    public function setUp(): void
    {
        $parser = new DocumentParser();
        $document = new TextDocument('file:///tmp/Foo.php', $this->loadFixture(), 0);
        $this->subject = $parser->parse($document);
    }

    private function loadFixture()
    {
        return file_get_contents(__DIR__.'/../fixtures/Foo.php');
    }

    public function testGetMethodAtCursor()
    {
        $method = $this->subject->getMethodAtCursor(14, 35);

        $this->assertEquals('testFunction', $method->name);
    }

    public function testGetClassName()
    {
        $this->assertEquals('Fixtures\Foo', $this->subject->getClassName());
    }

    public function testGetMethod()
    {
        $method = $this->subject->getMethod('anotherTestFunction');

        $this->assertEquals('anotherTestFunction', $method->name);
    }
}
