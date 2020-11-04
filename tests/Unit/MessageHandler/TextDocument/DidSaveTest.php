<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\MessageHandler\TextDocument;

use LanguageServer\MessageHandler\TextDocument\DidSave;
use LanguageServer\ParsedDocument;
use LanguageServer\Parser\DocumentParser;
use LanguageServer\Server\Protocol\NotificationMessage;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;

class DidSaveTest extends TestCase
{
    public function testDidSave(): void
    {
        $registry = $this->createMock(TextDocumentRegistry::class);
        $parser   = $this->createMock(DocumentParser::class);
        $subject  = new DidSave($registry, $parser);
        $document = new ParsedDocument('file:///tmp/foo.php', '<?php echo "Hi";?>', []);

        $parser
            ->method('parseFromFile')
            ->willReturn($document);

        $registry
            ->expects($this->once())
            ->method('add')
            ->with($document);

        $request = new NotificationMessage('textDocument/didSave', [
            'textDocument' => ['uri' => 'file:///tmp/foo.php'],
        ]);

        $subject->__invoke($request, function (): void {
            $this->fail('The next method should never be called');
        });
    }
}
