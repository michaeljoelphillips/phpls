<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method;

use DI\Container;
use LanguageServer\Exception\ServerNotInitializedException;
use LanguageServer\Method\Initialize;
use LanguageServer\Server\Protocol\RequestMessage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class InitializeTest extends TestCase
{
    public function testInitialize()
    {
        $container = $this->createMock(Container::class);
        $subject = new Initialize($container);

        $container
            ->expects($this->once())
            ->method('set')
            ->with('project_root', '/tmp');

        $next = function () {
            $this->fail('The next method should never be called');
        };

        $response = $subject->__invoke(new RequestMessage(1, 'initialize', ['rootUri' => 'file:///tmp']), $next);

        $this->assertEquals([':', '>'], $response->result->capabilities->completionProvider->triggerCharacters);
        $this->assertEquals(['(', ','], $response->result->capabilities->signatureHelpProvider->triggerCharacters);
    }

    public function testInitializeWithEmptyProjectRoot()
    {
        $container = $this->createMock(Container::class);
        $subject = new Initialize($container);

        $this->expectException(RuntimeException::class);

        $next = function () {
            $this->fail('The next method should never be called');
        };

        $result = $subject(new RequestMessage(1, 'initialize', ['rootUri' => null]), $next);
    }

    public function testWhenInitializationRequestWasNotSentFirst()
    {
        $container = $this->createMock(Container::class);
        $subject = new Initialize($container);

        $next = function () {
            $this->fail('The next middleware should never be called');
        };

        $this->expectException(ServerNotInitializedException::class);

        $subject->__invoke(new RequestMessage(1, 'textDocument/completion', []), $next);
    }

    public function testWhenInitializationRequestWasSentFirst()
    {
        $container = $this->createMock(Container::class);
        $subject = new Initialize($container);

        $next = function () {
            $this->addToAssertionCount(1);
        };

        $subject->__invoke(new RequestMessage(1, 'initialize', ['rootUri' => 'file:///tmp']), $next);
        $subject->__invoke(new RequestMessage(1, 'textDocument/didOpen', []), $next);
    }

    public function testWhenInitializationRequestWasSentButExitWasInvoked()
    {
        $container = $this->createMock(Container::class);
        $subject = new Initialize($container);

        $next = function () {
            $this->addToAssertionCount(1);
        };

        $subject->__invoke(new RequestMessage(1, 'exit', []), $next);
    }
}
