<?php

declare(strict_types=1);

namespace LanguageServer\RPC;

use LanguageServer\LSP\Response\InitializeResponse;
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
