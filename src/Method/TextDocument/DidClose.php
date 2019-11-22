<?php

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Method\NotificationHandlerInterface;
use LanguageServer\Method\RemoteMethodInterface;

class DidClose implements RemoteMethodInterface, NotificationHandlerInterface
{
    public function __invoke(array $params)
    {
        return;
    }
}
