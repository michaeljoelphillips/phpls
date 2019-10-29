<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument;

use LanguageServer\Method\TextDocument\DidSave;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;

class DidSaveTest extends TestCase
{
    public function testDidSave()
    {
        $registry = $this->createMock(TextDocumentRegistry::class);
        $subject = new DidSave($registry);

        $registry
            ->expects($this->once())
            ->method('add');

        $subject->__invoke([
            'textDocument' => [
                'uri' => __DIR__.'/../../fixtures/ParsedDocumentFixture.php'
            ],
        ]);
    }
}
