<?php

declare(strict_types=1);

namespace LanguageServer\Test\Parser;

use LanguageServer\Parser\DocumentParser;
use LanguageServer\TextDocument;
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
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        $this->subject = new DocumentParser($parser);
    }

    public function testParse()
    {
        $document = new TextDocument('file:///tmp/Foo.php', '<?php echo "Hi";', 0);

        $parsedDocument = $this->subject->parse($document);

        $this->assertNotEmpty($parsedDocument->getNodes());
    }
}
