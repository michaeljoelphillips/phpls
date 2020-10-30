<?php

declare(strict_types=1);

namespace LanguageServer\Server\Protocol;

abstract class Message
{
    public string $jsonrpc = '2.0';

    public string $method;

    public function __construct(string $method)
    {
        $this->method = $method;
    }
}
