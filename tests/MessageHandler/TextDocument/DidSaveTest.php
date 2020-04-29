<?php

declare(strict_types=1);

namespace LanguageServer\Test\MessageHandler\TextDocument;

use LanguageServer\MessageHandler\TextDocument\DidSave;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\TextDocumentRegistry;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;

class DidSaveTest extends TestCase
{
    public function testDidSave() : void
    {
        $registry = $this->createMock(TextDocumentRegistry::class);
        $parser   = $this->createMock(Parser::class);
        $subject  = new DidSave($registry, $parser);

        $registry
            ->expects($this->once())
            ->method('add');

        $parser
            ->method('parse')
            ->willReturn([]);

        $request = new RequestMessage(1, 'textDocument/didSave', [
            'textDocument' => [
                'uri' => __DIR__ . '/../../fixtures/ParsedDocumentFixture.php',
            ],
        ]);

        $next = function () : void {
            $this->fail('The next method should never be called');
        };

        $subject->__invoke($request, $next);
    }
}
