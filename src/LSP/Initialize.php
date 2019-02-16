<?php

namespace LanguageServer\LSP;

use LanguageServer\RPC\Request;
use LanguageServer\RPC\Server;
use React\Stream\WritableResourceStream;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Initialize
{
    /** @var WritableResourceStream */
    private $output;

    /**
     * @param Server                 $server
     * @param WritableResourceStream $output
     */
    public function __construct(Server $server, WritableResourceStream $output)
    {
        $this->output = $output;

        $server->on('initialize', [$this, 'handle']);
    }

    public function handle(Request $request)
    {
    }
}
