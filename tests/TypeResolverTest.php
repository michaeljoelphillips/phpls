<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\Parser\DocumentParser;
use LanguageServer\TextDocument;
use LanguageServer\TypeResolver;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TypeResolverTest extends TestCase
{
    public function setUp(): void
    {
        $parser = new DocumentParser();
        $document = new TextDocument('file:///tmp/Foo.php', $this->loadFixture(), 0);
        $this->parsedDocument = $parser->parse($document);
        $this->subject = new TypeResolver();
    }

    private function loadFixture()
    {
        return file_get_contents(__DIR__.'/../fixtures/Foo.php');
    }

    public function testGetTypeForInstanceMethodCall()
    {
        $method = $this->parsedDocument->getMethodAtCursor(14, 35);
        $type = $this->subject->getType($this->parsedDocument, $method);

        $this->assertEquals('Fixtures\Foo', $type);
    }

    public function testGetTypeForObjectMethodCall()
    {
    }
}
