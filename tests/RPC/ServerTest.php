<?php

declare(strict_types=1);

namespace Tests\RPC;

use LanguageServer\RPC\MessageSerializer;
use LanguageServer\RPC\Server;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use React\Stream\WritableStreamInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ServerTest extends TestCase
{
    private $container;
    private $serializer;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->serializer = $this->createMock(MessageSerializer::class);
    }

    public function testHandle(): void
    {
        $subject = new Server($this->container, $this->serializer);

        $this->container->method('get')->willReturn(new \stdClass());

        $connection = $this->createMock(WritableStreamInterface::class);

        $subject->handle('', $connection);
    }
}
