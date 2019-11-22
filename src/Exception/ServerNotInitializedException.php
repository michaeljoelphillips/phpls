<?php

namespace LanguageServer\Exception;

use LanguageServer\Exception\LanguageServerException;

class ServerNotInitializedException extends LanguageServerException
{
    private const MESSAGE = 'The server has not been initialized';

    public function __construct()
    {
        parent::__construct(self::MESSAGE, -32002);
    }
}
