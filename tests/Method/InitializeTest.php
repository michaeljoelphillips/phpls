<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method;

use DI\Container;
use LanguageServer\MessageHandler\Initialize;
use LanguageServer\Server\Exception\ServerNotInitialized;
use LanguageServer\Server\Protocol\RequestMessage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class InitializeTest extends TestCase
{
    public function testInitialize() : void
    {
        $container = $this->createMock(Container::class);
        $subject   = new Initialize($container);

        $container
            ->expects($this->once())
            ->method('set')
            ->with('project_root', '/tmp');

        $next = function () : void {
            $this->fail('The next method should never be called');
        };

        $response = $subject->__invoke(new RequestMessage(1, 'initialize', ['rootUri' => 'file:///tmp']), $next);

        $this->assertEquals([':', '>'], $response->result->capabilities->completionProvider->triggerCharacters);
        $this->assertEquals(['(', ','], $response->result->capabilities->signatureHelpProvider->triggerCharacters);
    }

    public function testInitializeWithEmptyProjectRoot() : void
    {
        $container = $this->createMock(Container::class);
        $subject   = new Initialize($container);

        $this->expectException(RuntimeException::class);

        $next = function () : void {
            $this->fail('The next method should never be called');
        };

        $result = $subject(new RequestMessage(1, 'initialize', ['rootUri' => null]), $next);
    }

    public function testWhenInitializationRequestWasNotSentFirst() : void
    {
        $container = $this->createMock(Container::class);
        $subject   = new Initialize($container);

        $next = function () : void {
            $this->fail('The next middleware should never be called');
        };

        $this->expectException(ServerNotInitialized::class);

        $subject->__invoke(new RequestMessage(1, 'textDocument/completion', []), $next);
    }

    public function testWhenInitializationRequestWasSentFirst() : void
    {
        $container = $this->createMock(Container::class);
        $subject   = new Initialize($container);

        $next = function () : void {
            $this->addToAssertionCount(1);
        };

        $subject->__invoke(new RequestMessage(1, 'initialize', ['rootUri' => 'file:///tmp']), $next);
        $subject->__invoke(new RequestMessage(1, 'textDocument/didOpen', []), $next);
    }

    public function testWhenInitializationRequestWasSentButExitWasInvoked() : void
    {
        $container = $this->createMock(Container::class);
        $subject   = new Initialize($container);

        $next = function () : void {
            $this->addToAssertionCount(1);
        };

        $subject->__invoke(new RequestMessage(1, 'exit', []), $next);
    }
}
