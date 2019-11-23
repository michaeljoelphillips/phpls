<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Method\NotificationHandlerInterface;
use LanguageServer\Method\RequestHandlerInterface;
use LanguageServer\Server\Protocol\Message;

class DidClose implements NotificationHandlerInterface
{
    public function __invoke(Message $request)
    {
        return;
    }
}
