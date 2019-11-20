<?php

namespace LanguageServer\Server;

class ResponseError
{
    public $code = -32603;
    public $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
}
