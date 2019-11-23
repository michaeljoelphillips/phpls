<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method;

use DI\Container;
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
            ->with('project_root', '/tmp/foo');

        $response = $subject->__invoke(new RequestMessage(1, 'initialize', ['rootUri' => 'file:///tmp/foo']));

        $this->assertEquals([':', '>'], $response->result->capabilities->completionProvider->triggerCharacters);
        $this->assertEquals(['(', ','], $response->result->capabilities->signatureHelpProvider->triggerCharacters);
    }

    public function testInitializeWithEmptyProjectRoot()
    {
        $container = $this->createMock(Container::class);
        $subject = new Initialize($container);

        $this->expectException(RuntimeException::class);

        $result = $subject(new RequestMessage(1, 'initialize', ['rootUri' => null]));
    }
}
