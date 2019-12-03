<?php

declare(strict_types=1);

namespace LanguageServer\Server\Middleware;

use LanguageServer\Exception\ServerNotInitializedException;
use LanguageServer\Server\Protocol\Message;

class WaitForInitializationRequest
{
    private bool $initializationRequestReceieved = false;

    public function __invoke(Message $message, callable $next)
    {
        if ('initialize' === $message->method) {
            $this->initializationRequestReceieved = true;
        }

        if (true === $this->initializationRequestReceieved || 'exit' === $message->method) {
            return $next->__invoke($message);
        }

        throw new ServerNotInitializedException();
    }
}
