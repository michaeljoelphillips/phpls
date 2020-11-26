<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Completion\Providers;

use LanguageServer\Completion\CompletionProvider;
use LanguageServer\Completion\DocumentCompletor;
use LanguageServer\CursorPosition;
use LanguageServer\Inference\TypeResolver;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\CompletionItem;
use PhpParser\NodeAbstract;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\Reflector;

class CompletionProviderTest extends TestCase
{
    public function testCompletionServiceDoesNotReflectForNonTypeBasedProviders(): void
    {
        $typeResolver = $this->createMock(TypeResolver::class);
        $reflector    = $this->createMock(Reflector::class);
        $provider     = $this->createMock(DocumentCompletor::class);

        $subject = new CompletionProvider($reflector, $typeResolver, [$provider], []);

        $document       = $this->createMock(ParsedDocument::class);
        $cursorPosition = $this->createMock(CursorPosition::class);

        $document
            ->method('searchNodesAtCursor')
            ->willReturn([$this->createMock(NodeAbstract::class)]);

        $typeResolver
            ->expects($this->never())
            ->method('getType')
            ->willReturn('stdClass');

        $reflector
            ->expects($this->never())
            ->method('reflect');

        $provider
            ->method('supports')
            ->willReturn(true);

        $provider
            ->method('complete')
            ->willReturn([new CompletionItem('Test Item')]);

        $result = $subject->complete($document, $cursorPosition);

        self::assertEquals('Test Item', $result->items[0]->label);
    }

    public function testCompletionServiceReturnsEmptyArrayWhenNoCompletableNodesWereFound(): void
    {
        $typeResolver = $this->createMock(TypeResolver::class);
        $reflector    = $this->createMock(Reflector::class);
        $provider     = $this->createMock(DocumentCompletor::class);

        $document       = $this->createMock(ParsedDocument::class);
        $cursorPosition = $this->createMock(CursorPosition::class);

        $provider
            ->method('supports')
            ->willReturn(false);

        $subject = new CompletionProvider($reflector, $typeResolver, [$provider], []);

        $completionList = $subject->complete($document, $cursorPosition);

        self::assertEquals([], $completionList->items);
    }
}
