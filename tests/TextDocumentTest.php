<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\CursorPosition;
use LanguageServer\TextDocument;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TextDocumentTest extends TestCase
{
    public function testGetNormalizedPath()
    {
        $subject = new TextDocument('file:///tmp/foo.php', '<?php ', 0);

        $this->assertEquals('/tmp/foo.php', $subject->getNormalizedPath());
    }

    public function testGetCursorPosition()
    {
        $subject = new TextDocument('file:///tmp/Foo.php', $this->getFixture(), 0);
        $cursor = new CursorPosition(10, 32, 151);

        $this->assertEquals($cursor, $subject->getCursorPosition(10, 32));
    }

    private function getFixture()
    {
        return file_get_contents('tests/fixtures/TextDocumentFixture.php');
    }
}
