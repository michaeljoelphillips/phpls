<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use LanguageServer\Server\Exception\ParseError;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;

interface MessageSerializer
{
    /**
     * @throws ParseError
     */
    public function deserialize(string $request) : ?Message;

    public function serialize(ResponseMessage $response) : string;
}
