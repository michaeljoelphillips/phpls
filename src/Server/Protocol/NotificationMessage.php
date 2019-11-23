<?php

namespace LanguageServer\Server\Protocol;

class NotificationMessage extends Message
{
    public $method;
    public $params;

    public function __construct(string $method, ?array $params)
    {
        $this->method = $method;
        $this->params = $params;
    }
}
