<?php

declare(strict_types=1);

namespace LanguageServer\Test\Server;

use LanguageServer\Server\MessageSerializer;
use LanguageServer\Server\Server;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\Socket\ServerInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ServerTest extends TestCase
{
    private $container;
    private $serializer;
    private $logger;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->serializer = $this->createMock(MessageSerializer::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testListen(): void
    {
        $subject = new Server($this->container, $this->serializer, $this->logger);
        $this->container->method('get')->willReturn(new \stdClass());
        $server = $this->createMock(ServerInterface::class);

        $server
            ->expects($this->once())
            ->method('on');

        $subject->listen($server);
    }
}
