<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method;

use DI\Container;
use LanguageServer\Method\Initialize;
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

        $result = $subject([
            'rootUri' => 'file:///tmp/foo',
        ]);

        $this->assertEquals([':', '>'], $result->completionProvider->triggerCharacters);
        $this->assertEquals(['(', ','], $result->signatureHelpProvider->triggerCharacters);
    }

    public function testInitializeWithEmptyProjectRoot()
    {
        $container = $this->createMock(Container::class);
        $subject = new Initialize($container);

        $this->expectException(RuntimeException::class);

        $result = $subject([]);
    }
}
