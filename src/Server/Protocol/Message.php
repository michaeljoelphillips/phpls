<?php

declare(strict_types=1);

namespace LanguageServer\Server\Protocol;

abstract class Message
{
    public string $jsonrpc = '2.0';
}
