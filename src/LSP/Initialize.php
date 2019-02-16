<?php

namespace LanguageServer\LSP;

use LanguageServer\RPC\Request;
use LanguageServer\RPC\Server;
use React\Stream\WritableStreamInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Initialize
{
    /**
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $server->on('initialize', [$this, 'handle']);
    }

    public function handle(Request $request, WritableStreamInterface $connection)
    {
        $connection->write('Hi!');
    }
}
