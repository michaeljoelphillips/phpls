<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\MessageHandler\TextDocument;

use LanguageServer\Completion\CompletionProvider;
use LanguageServer\Completion\Completors\InstanceMethodCompletor;
use LanguageServer\Completion\Completors\InstanceVariableCompletor;
use LanguageServer\Inference\TypeResolver;
use LanguageServer\MessageHandler\TextDocument\Completion;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Server\Protocol\ResponseMessage;
use LanguageServer\Test\Unit\ParserTestCase;
use LanguageServer\TextDocumentRegistry;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionList;

class CompletionTest extends ParserTestCase
{
    private TextDocumentRegistry $registry;
    private Completion $subject;

    public function setUp(): void
    {
        $this->registry = new TextDocumentRegistry();
        $classReflector = $this->getClassReflector();
        $typeResolver   = new TypeResolver($classReflector);

        $provider = new CompletionProvider(
            $classReflector,
            $typeResolver,
            [],
            [
                new InstanceMethodCompletor(),
                new InstanceVariableCompletor(),
            ]
        );

        $this->subject = new Completion($this->registry, $provider);
    }

    public function testCompleteWhenExpressionIsCompletable(): void
    {
        $document = $this->parseFixture('CompletionProviderFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/completion', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 12,
                'character' => 15,
            ],
        ]);

        $response = $this->subject->__invoke(
            $request,
            function (): void {
                $this->fail('Next should never be called');
            }
        );

        self::assertInstanceOf(ResponseMessage::class, $response);
        self::assertInstanceOf(CompletionList::class, $response->result);
        self::assertCount(5, $response->result->items);
        self::assertContainsOnlyInstancesOf(CompletionItem::class, $response->result->items);
    }

    public function testCompleteWhenExpressionIsNotCompletable(): void
    {
        $document = $this->parseFixture('TypeResolverFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/completion', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 1,
                'character' => 1,
            ],
        ]);

        $next = function (): void {
            $this->fail('Next should never be called');
        };

        $response = $this->subject->__invoke($request, $next);

        self::assertInstanceOf(ResponseMessage::class, $response);
        self::assertInstanceOf(CompletionList::class, $response->result);
        self::assertEmpty($response->result->items);
    }

    public function testCompleteWhenTypeCannotBeResolved(): void
    {
        $document = $this->parseFixture('CompletionProviderFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/completion', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 17,
                'character' => 22,
            ],
        ]);

        $response = $this->subject->__invoke(
            $request,
            function (): void {
                $this->fail('Next should never be called');
            }
        );

        self::assertInstanceOf(ResponseMessage::class, $response);
        self::assertInstanceOf(CompletionList::class, $response->result);
        self::assertEmpty($response->result->items);
    }

    public function testCompleteWithPartialIdentifier(): void
    {
        $document = $this->parseFixture('CompletionProviderFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/completion', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 27,
                'character' => 27,
            ],
        ]);

        $response = $this->subject->__invoke(
            $request,
            function (): void {
                $this->fail('Next should never be called');
            }
        );

        self::assertInstanceOf(ResponseMessage::class, $response);
        self::assertInstanceOf(CompletionList::class, $response->result);
        self::assertNotEmpty($response->result->items);
    }
}
