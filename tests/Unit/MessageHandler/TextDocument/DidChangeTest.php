<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\MessageHandler\TextDocument;

use LanguageServer\MessageHandler\TextDocument\DidChange;
use LanguageServer\ParsedDocument;
use LanguageServer\Parser\DocumentParser;
use LanguageServer\Server\Protocol\NotificationMessage;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;

class DidChangeTest extends TestCase
{
    public function testDidChange(): void
    {
        $registry = $this->createMock(TextDocumentRegistry::class);
        $parser   = $this->createMock(DocumentParser::class);
        $subject  = new DidChange($registry, $parser);
        $document = new ParsedDocument('file:///tmp/foo.php', '<?php echo "Hi";?>', []);

        $parser
            ->expects($this->once())
            ->method('parse')
            ->with('file:///tmp/foo.php', '<?php echo "Hi";?>')
            ->willReturn($document);

        $registry
            ->expects($this->once())
            ->method('add');

        $request = new NotificationMessage('textDocument/didChange', [
            'textDocument' => [
                'uri' => 'file:///tmp/foo.php',
                'version' => 1,
            ],
            'contentChanges' => [
                ['text' => '<?php echo "Hi";?>'],
            ],
        ]);

        $subject->__invoke($request, function (): void {
            $this->fail('The next method should never be called');
        });
    }
}
