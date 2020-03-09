<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\CursorPosition;
use LanguageServer\TextDocument;

class TextDocumentTest extends FixtureTestCase
{
    public function testGetters() : void
    {
        $subject = new TextDocument('file:///tmp/Foo.php', $source = $this->loadFixture('TextDocumentFixture.php'), 0);

        $this->assertEquals('file:///tmp/Foo.php', $subject->getUri());
        $this->assertEquals($source, $subject->getSource());
        $this->assertEquals(0, $subject->getVersion());
    }

    public function testGetCursorPosition() : void
    {
        $subject = new TextDocument('file:///tmp/Foo.php', $this->loadFixture('TextDocumentFixture.php'), 0);
        $cursor  = new CursorPosition(10, 32, 151);

        $this->assertEquals($cursor, $subject->getCursorPosition(10, 32));
    }
}
