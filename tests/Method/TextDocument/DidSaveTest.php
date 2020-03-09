<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument;

use LanguageServer\Method\TextDocument\DidSave;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;

class DidSaveTest extends TestCase
{
    public function testDidSave() : void
    {
        $registry = $this->createMock(TextDocumentRegistry::class);
        $subject  = new DidSave($registry);

        $registry
            ->expects($this->once())
            ->method('add');

        $request = new RequestMessage(1, 'textDocument/didSave', [
            'textDocument' => [
                'uri' => __DIR__ . '/../../fixtures/ParsedDocumentFixture.php',
            ],
        ]);

        $next = function () : void {
            $this->fail('The next method should never be called');
        };

        $subject = new DidSave($registry);

        $subject->__invoke($request, $next);
    }
}
