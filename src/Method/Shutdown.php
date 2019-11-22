<?php

namespace LanguageServer\Method;

use LanguageServer\Method\RemoteMethodInterface;
use LanguageServer\Server\Server;

class Shutdown implements RemoteMethodInterface
{
    private $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function __invoke(array $params)
    {
        $this->server->shutdown();
    }
}
