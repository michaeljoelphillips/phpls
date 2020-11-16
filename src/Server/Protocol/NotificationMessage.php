<?php

declare(strict_types=1);

namespace LanguageServer\Server\Protocol;

class NotificationMessage extends Message
{
    /** @var array<string, mixed>|object */
    public $params;

    /**
     * @param array<string, mixed>|object $params
     */
    public function __construct(string $method, $params)
    {
        parent::__construct($method);

        $this->params = $params;
    }
}
