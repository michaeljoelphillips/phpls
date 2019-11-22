<?php

namespace LanguageServer\Test\Method;

use LanguageServer\Method\Shutdown;
use LanguageServer\Server\Server;
use PHPUnit\Framework\TestCase;

class ShutdownTest extends TestCase
{
    public function testShutdown()
    {
        $server = $this->createMock(Server::class);

        $subject = new Shutdown($server);

        $server
            ->expects($this->once())
            ->method('shutdown');

        $subject->__invoke([]);
    }
}
