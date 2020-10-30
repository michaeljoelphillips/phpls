<?php

declare(strict_types=1);

namespace LanguageServer\Server\Protocol;

class RequestMessage extends Message
{
    public int $id;

    /** @var array<string, mixed>|null */
    public ?array $params;

    /**
     * @param array<string, mixed>|null $params
     */
    public function __construct(int $id, string $method, ?array $params)
    {
        parent::__construct($method);

        $this->id     = $id;
        $this->params = $params;
    }
}
