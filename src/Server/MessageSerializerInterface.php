<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use LanguageServer\Exception\ParseErrorException;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;

interface MessageSerializerInterface
{
    /**
     * @throws ParseErrorException
     */
    public function deserialize(string $request): ?Message;

    public function serialize(ResponseMessage $response): string;
}
