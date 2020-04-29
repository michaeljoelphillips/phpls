<?php

declare(strict_types=1);

namespace LanguageServer\Server\Exception;

use LanguageServerProtocol\ErrorCode;

class InvalidRequest extends LanguageServerException
{
    private const MESSAGE = 'The server has been shutdown and may no longer receive requests';

    public function __construct()
    {
        parent::__construct(self::MESSAGE, ErrorCode::INVALID_REQUEST);
    }
}
