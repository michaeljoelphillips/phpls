<?php

declare(strict_types=1);

namespace LanguageServer\Server\Exception;

use LanguageServerProtocol\ErrorCode;

class ParseError extends LanguageServerException
{
    private const MESSAGE = 'The server could not parse the request';

    public function __construct()
    {
        parent::__construct(self::MESSAGE, ErrorCode::PARSE_ERROR);
    }
}
