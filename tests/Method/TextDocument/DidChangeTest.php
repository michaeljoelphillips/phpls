<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument;

use LanguageServer\Method\TextDocument\DidChange;
use LanguageServer\Parser\DocumentParser;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;

class DidChangeTest extends TestCase
{
    public function testDidChange() : void
    {
        $registry = $this->createMock(TextDocumentRegistry::class);
        $parser   = $this->createMock(DocumentParser::class);

        $parser
            ->expects($this->once())
            ->method('parse');

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
