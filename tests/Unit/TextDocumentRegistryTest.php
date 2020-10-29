<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit;

use LanguageServer\ParsedDocument;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;

class TextDocumentRegistryTest extends TestCase
{
    public function testAddOnlyStoresLatestVersion(): void
    {
        $subject    = new TextDocumentRegistry();
        $versionOne = new ParsedDocument('file:///tmp/foo.php', '<?php ', []);
        $versionTwo = new ParsedDocument('file:///tmp/foo.php', '<?php ', []);

        $subject->add($versionOne);
        $subject->add($versionTwo);

        $this->assertSame($versionTwo, $subject->get('file:///tmp/foo.php'));
    }

    public function testGetAll(): void
    {
        $subject    = new TextDocumentRegistry();
        $versionOne = new ParsedDocument('file:///tmp/foo.php', '<?php ', []);
        $versionTwo = new ParsedDocument('file:///tmp/bar.php', '<?php ', []);

        $subject->add($versionOne);
        $subject->add($versionTwo);

        $this->assertCount(2, $subject->getAll());
    }

    public function testClear(): void
    {
        $subject    = new TextDocumentRegistry();
        $versionOne = new ParsedDocument('file:///tmp/foo.php', '<?php ', []);

        $subject->add($versionOne);
        $subject->clear();

        $this->assertEmpty($subject->getAll());
    }
}
