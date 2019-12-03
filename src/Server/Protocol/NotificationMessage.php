<?php

declare(strict_types=1);

namespace LanguageServer\Server\Protocol;

class NotificationMessage extends Message
{
    public string $method;
    public ?array $params;

    public function __construct(string $method, ?array $params)
    {
        $this->method = $method;
        $this->params = $params;
    }
}
