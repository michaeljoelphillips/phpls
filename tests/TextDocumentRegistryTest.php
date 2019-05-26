<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TextDocumentRegistryTest extends TestCase
{
    public function testAdd()
    {
        $subject = new TextDocumentRegistry();
        $versionOne = new TextDocument('file:///tmp/foo.php', '<?php ', 0);
        $versionTwo = new TextDocument('file:///tmp/foo.php', '<?php ', 1);

        $subject->add($versionOne);
        $subject->add($versionTwo);

        $this->assertSame($versionTwo, $subject->get('file:///tmp/foo.php'));
    }

    public function testGetAll()
    {
        $subject = new TextDocumentRegistry();
        $versionOne = new TextDocument('file:///tmp/foo.php', '<?php ', 0);
        $versionTwo = new TextDocument('file:///tmp/bar.php', '<?php ', 1);

        $subject->add($versionOne);
        $subject->add($versionTwo);

        $this->assertCount(2, $subject->getAll());
    }

    public function testClear()
    {
        $subject = new TextDocumentRegistry();
        $versionOne = new TextDocument('file:///tmp/foo.php', '<?php ', 0);

        $subject->add($versionOne);
        $subject->clear();

        $this->assertEmpty($subject->getAll());
    }
}
