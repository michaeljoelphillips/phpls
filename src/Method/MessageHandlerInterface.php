<?php

declare(strict_types=1);

namespace LanguageServer\Method;

use LanguageServer\Server\Protocol\Message;

interface MessageHandlerInterface
{
    public function __invoke(Message $request);
}
