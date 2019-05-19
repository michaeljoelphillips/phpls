<?php

declare(strict_types=1);

namespace Test\LSP;

use LanguageServer\LSP\DocumentParser;
use LanguageServer\LSP\TextDocument;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DocumentParserTest extends TestCase
{
    private $subject;

    public function setUp(): void
    {
        $this->subject = new DocumentParser(ParserFactory::create(ParserFactory::PREFER_PHP7));
    }

    public function testParse()
    {
        $document = new TextDocument('file:///tmp/Foo.php', '<?php echo "Hi";', 0);
        $parsedDocument = $this->subject->parse($document);

        $this->assertNotEmpty($parsedDocument->getNodes());
    }
}
