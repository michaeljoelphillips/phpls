<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument;

use LanguageServer\Method\TextDocument\DidChange;
use LanguageServer\Parser\DocumentParserInterface;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DidChangeTest extends TestCase
{
    public function testDidChange()
    {
        $registry = $this->createMock(TextDocumentRegistry::class);
        $parser = $this->createMock(DocumentParserInterface::class);

        $parser
            ->expects($this->once())
            ->method('parse');

        $registry
            ->expects($this->once())
            ->method('add');

        $subject = new DidChange($registry, $parser);

        $subject(new RequestMessage(1, 'textDocument/didChange', [
            'textDocument' => [
                'uri' => 'file:///tmp/foo.php',
                'version' => 1,
            ],
            'contentChanges' => [
                [
                    'text' => '<?php echo "Hi";?>',
                ],
            ],
        ]));
    }
}
