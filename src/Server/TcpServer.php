<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use Closure;
use React\Socket\ServerInterface;
use React\Stream\DuplexStreamInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TcpServer extends Server
{
    /**
     * @param ServerInterface $server
     */
    public function __construct(ServerInterface $server)
    {
        $server->on('connection', Closure::fromCallable([$this, 'registerDataHandler']));
    }

    private function registerDataHandler(DuplexStreamInterface $connection)
    {
        $connection->on('data', function (string $data) use ($connection) {
            $this->handle($data, $connection);
        });
    }
}
