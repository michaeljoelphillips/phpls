<?php

declare(strict_types=1);

namespace LanguageServer\Server\MessageHandler;

use LanguageServer\Exception\ServerNotInitializedException;
use LanguageServer\Method\MessageHandlerInterface;
use LanguageServer\Server\Protocol\Message;

class WaitForInitializationRequest implements MessageHandlerInterface
{
    private bool $initializationRequestReceieved = false;

    public function __invoke(Message $message, callable $next)
    {
        if ('initialize' === $message->method) {
            $this->initializationRequestReceieved = true;
        }

        if (false === $this->initializationRequestReceieved && 'exit' !== $message->method) {
            throw new ServerNotInitializedException();
        }

        return $next->__invoke($message);
    }
}
