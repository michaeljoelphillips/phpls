<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;

class DidClose implements MessageHandler
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $request, callable $next)
    {
        return $next($request);
    }
}
