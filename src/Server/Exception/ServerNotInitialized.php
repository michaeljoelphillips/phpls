<?php

declare(strict_types=1);

namespace LanguageServer\Server\Exception;

class ServerNotInitialized extends LanguageServerException
{
    private const MESSAGE = 'The server has not been initialized';

    public function __construct()
    {
        parent::__construct(self::MESSAGE, -32002);
    }
}
