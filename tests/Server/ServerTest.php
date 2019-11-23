<?php

declare(strict_types=1);

namespace LanguageServer\Test\Server;

use LanguageServer\Server\JsonRpcServer;
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
        $this->jsonrpcServer = $this->createMock(JsonRpcServer::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }
}
