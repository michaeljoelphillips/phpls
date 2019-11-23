<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument;

use LanguageServer\Method\TextDocument\DidSave;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;
use LanguageServer\Server\Protocol\RequestMessage;

class DidSaveTest extends TestCase
{
    public function testDidSave()
    {
        $registry = $this->createMock(TextDocumentRegistry::class);
        $subject = new DidSave($registry);

        $registry
            ->expects($this->once())
            ->method('add');

        $subject->__invoke(new RequestMessage(1, 'textDocument/didSave', [
            'textDocument' => [
                'uri' => __DIR__.'/../../fixtures/ParsedDocumentFixture.php'
            ],
        ]));
    }
}
