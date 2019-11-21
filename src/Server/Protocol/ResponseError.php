<?php

namespace LanguageServer\Server\Protocol;

use LanguageServer\Exception\LanguageServerException;
use LanguageServerProtocol\ErrorCode;
use Throwable;

class ResponseError
{
    public $code;
    public $message;

    public function __construct(Throwable $t)
    {
        $this->message = $t->getMessage();

        if ($t instanceof LanguageServerException) {
            $this->code = $t->getCode();

            return;
        }

        $this->code = ErrorCode::INTERNAL_ERROR;
    }
}
