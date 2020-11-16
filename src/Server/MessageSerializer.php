<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use LanguageServer\Server\Exception\ParseError;
use LanguageServer\Server\Protocol\Message;

interface MessageSerializer
{
    /**
     * @throws ParseError
     */
    public function deserialize(string $request): ?Message;

    public function serialize(Message $response): string;
}
