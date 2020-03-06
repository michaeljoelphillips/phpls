<?php

declare(strict_types=1);

namespace LanguageServer\Method;

use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;

class Initialized implements MessageHandler
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $message, callable $next)
    {
        if ($message->method !== 'initialized') {
            return $next->__invoke($message);
        }

        return;
    }
}
