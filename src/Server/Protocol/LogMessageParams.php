<?php

declare(strict_types=1);

namespace LanguageServer\Server\Protocol;

class LogMessageParams
{
    public int $type;
    public string $message;

    public function __construct(string $message, int $type)
    {
        $this->message = $message;
        $this->type    = $type;
    }
}
