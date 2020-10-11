<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\MessageHandler\TextDocument;

use LanguageServer\MessageHandler\TextDocument\DidChange;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\TextDocumentRegistry;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;

class DidChangeTest extends TestCase
{
    public function testDidChange() : void
    {
        $registry = $this->createMock(TextDocumentRegistry::class);
        $parser   = $this->createMock(Parser::class);

        $parser
            ->expects($this->once())
            ->method('parse')
            ->willReturn([]);

        $registry
            ->expects($this->once())
            ->method('add');

        $request = new RequestMessage(1, 'textDocument/didChange', [
            'textDocument' => [
                'uri' => 'file:///tmp/foo.php',
                'version' => 1,
            ],
            'contentChanges' => [
                ['text' => '<?php echo "Hi";?>'],
            ],
        ]);

        $next = function () : void {
            $this->fail('The next method should never be called');
        };

        $subject = new DidChange($registry, $parser);

        $subject->__invoke($request, $next);
    }
}
