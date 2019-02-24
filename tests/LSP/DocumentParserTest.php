<?php

declare(strict_types=1);

namespace Test\LSP;

use LanguageServer\LSP\DocumentParser;
use LanguageServer\LSP\TextDocument;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DocumentParserTest extends TestCase
{
    public function testParse()
    {
        $subject = new DocumentParser();

        $document = new TextDocument('file:///tmp/Foo.php', '<?php echo "Hi";', 0);
        $parsedDocument = $subject->parse($document);

        $this->assertNotEmpty($parsedDocument->getNodes());
    }

    public function testParseWithInvalidPHP()
    {
        $subject = new DocumentParser();

        $document = new TextDocument('file:///tmp/Foo.php', '<?php ', 0);
        $parsedDocument = $subject->parse($document);

        $this->assertEmpty($parsedDocument->getNodes());
    }
}
