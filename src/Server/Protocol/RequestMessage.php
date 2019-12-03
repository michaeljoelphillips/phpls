<?php

declare(strict_types=1);

namespace LanguageServer\Server\Protocol;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class RequestMessage extends Message
{
    public int $id;
    public string $method;
    public ?array $params;

    public function __construct(int $id, string $method, ?array $params)
    {
        $this->id = $id;
        $this->method = $method;
        $this->params = $params;
    }
}
