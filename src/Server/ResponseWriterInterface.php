<?php

namespace LanguageServer\Server;

use LanguageServer\Server\Protocol\ResponseMessage;

interface ResponseWriterInterface
{
    public function write(ResponseMessage $response): void;
}
