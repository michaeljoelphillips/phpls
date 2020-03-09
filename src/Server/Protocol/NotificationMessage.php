<?php

declare(strict_types=1);

namespace LanguageServer\Server\Protocol;

class NotificationMessage extends Message
{
    public string $method;

    /** @var array<string, mixed> */
    public ?array $params;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(string $method, ?array $params)
    {
        $this->method = $method;
        $this->params = $params;
    }
}
