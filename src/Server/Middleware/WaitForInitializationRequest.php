<?php

declare(strict_types=1);

namespace LanguageServer\Server\Middleware;

use LanguageServer\Exception\ServerNotInitializedException;
use LanguageServer\Server\Protocol\Message;

class WaitForInitializationRequest
{
    private $initializationRequestReceieved = false;

    public function __invoke(Message $message, callable $next)
    {
        if ($message->method === 'initialize') {
            $this->initializationRequestReceieved = true;
        }

        if (true === $this->initializationRequestReceieved || 'exit' === $message->method) {
            return $next->__invoke($message);
        }

        throw new ServerNotInitializedException();
    }
}
