<?php

declare(strict_types=1);

namespace LanguageServer\Test\MessageHandler\TextDocument;

use LanguageServer\Completion\CompletionProvider;
use LanguageServer\Completion\InstanceMethodProvider;
use LanguageServer\Inference\TypeResolver;
use LanguageServer\MessageHandler\TextDocument\Completion;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Server\Protocol\ResponseMessage;
use LanguageServer\Test\ParserTestCase;
use LanguageServer\TextDocumentRegistry;
use LanguageServerProtocol\CompletionList;
use Psr\Log\LoggerInterface;

class CompletionTest extends ParserTestCase
{
    private TextDocumentRegistry $registry;
    private CompletionProvider $completionProvider;
    private Completion $subject;

    public function setUp() : void
    {
        $this->registry           = new TextDocumentRegistry();
        $this->completionProvider = new InstanceMethodProvider();

        $classReflector = $this->getClassReflector();
        $typeResolver   = new TypeResolver($classReflector);

        $this->subject = new Completion(
            $this->registry,
            $classReflector,
            $typeResolver,
            $this->createMock(LoggerInterface::class),
            $this->completionProvider
        );
    }

    public function testCompleteWhenExpressionIsCompletable() : void
    {
        $document = $this->parse('CompletionProviderFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/completion', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 10,
                'character' => 15,
            ],
        ]);

        $response = $this->subject->__invoke(
            $request,
            fn() => $this->fail('Next should never be called')
        );

        self::assertInstanceOf(ResponseMessage::class, $response);
        self::assertInstanceOf(CompletionList::class, $response->result);
        self::assertNotEmpty($response->result->items);
    }

    public function testCompleteWhenExpressionIsNotCompletable() : void
    {
        $document = $this->parse('TypeResolverFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/completion', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 1,
                'character' => 1,
            ],
        ]);

        $next = fn() => $this->fail('Next should never be called');

        $response = $this->subject->__invoke($request, $next);

        self::assertInstanceOf(ResponseMessage::class, $response);
        self::assertInstanceOf(CompletionList::class, $response->result);
        self::assertEmpty($response->result->items);
    }

    public function testCompleteWhenTypeCannotBeResolved() : void
    {
        $document = $this->parse('CompletionProviderFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/completion', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 15,
                'character' => 22,
            ],
        ]);

        $response = $this->subject->__invoke(
            $request,
            fn() => $this->fail('Next should never be called')
        );

        self::assertInstanceOf(ResponseMessage::class, $response);
        self::assertInstanceOf(CompletionList::class, $response->result);
        self::assertEmpty($response->result->items);
    }

    public function testCompleteWithPartialIdentifier() : void
    {
        $document = $this->parse('CompletionProviderFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/completion', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 25,
                'character' => 27,
            ],
        ]);

        $response = $this->subject->__invoke(
            $request,
            fn() => $this->fail('Next should never be called')
        );

        self::assertInstanceOf(ResponseMessage::class, $response);
        self::assertInstanceOf(CompletionList::class, $response->result);
        self::assertNotEmpty($response->result->items);
    }
}
