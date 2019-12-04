<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Server\MessageHandlerInterface;
use LanguageServer\Server\Protocol\Message;

class DidClose implements MessageHandlerInterface
{
    public function __invoke(Message $request, callable $next)
    {
    }
}
