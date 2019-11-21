<?php

namespace LanguageServer\Exception;

use LanguageServer\Exception\LanguageServerException;
use LanguageServerProtocol\ErrorCode;

class InvalidRequestException extends LanguageServerException
{
    private const MESSAGE = 'The server has been shutdown and may no longer receive requests';

    public function __construct()
    {
        parent::__construct(self::MESSAGE, ErrorCode::INVALID_REQUEST);
    }
}
