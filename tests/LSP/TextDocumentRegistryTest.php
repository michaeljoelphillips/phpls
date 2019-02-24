<?php

declare(strict_types=1);

namespace Test\LSP;

use LanguageServer\LSP\TextDocument;
use LanguageServer\LSP\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TextDocumentRegistryTest extends TestCase
{
    public function testAddDocument()
    {
        $subject = new TextDocumentRegistry();
        $textDocument = new TextDocument('file:///tmp/foo.php', '<?php ', 0);

        $subject->add($textDocument);

        $this->assertSame($textDocument, $subject->get('file:///tmp/foo.php', 0));
    }

    public function testGetLatest()
    {
        $subject = new TextDocumentRegistry();
        $versionOne = new TextDocument('file:///tmp/foo.php', '<?php ', 0);
        $versionTwo = new TextDocument('file:///tmp/foo.php', '<?php ', 1);

        $subject->add($versionOne);
        $subject->add($versionTwo);

        $this->assertSame($versionTwo, $subject->getLatest('file:///tmp/foo.php'));
    }
}
