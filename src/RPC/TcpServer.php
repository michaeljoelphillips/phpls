<?php

namespace LanguageServer\RPC;

use React\Socket\ServerInterface;
use React\Stream\WritableStreamInterface;

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
        $server->on(
            'connection',
            function (WritableStreamInterface $connection) {
                $connection->on(
                    'data',
                    function (string $data) use ($connection) {
                        $this->handle($data, $connection);
                    }
                );
            }
        );
    }
}
