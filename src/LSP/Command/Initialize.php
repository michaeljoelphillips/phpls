<?php

declare(strict_types=1);

namespace LanguageServer\LSP\Command;

use LanguageServer\LSP\Response\InitializeResponse;
use LanguageServer\RPC\Request;
use LanguageServer\RPC\Server;
use React\Stream\WritableStreamInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Initialize
{
    /** @var Server */
    private $server;

    /**
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;

        $this->server->on('initialize', [$this, 'handle']);
    }

    public function handle(object $request, WritableStreamInterface $output)
    {
        $response = (string) new InitializeResponse($request->id);

        $output->write($response);
    }
}
