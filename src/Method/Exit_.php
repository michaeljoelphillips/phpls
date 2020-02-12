<?php

declare(strict_types=1);

namespace LanguageServer\Method;

use LanguageServer\Exception\InvalidRequestException;
use LanguageServer\Server\MessageHandlerInterface;
use LanguageServer\Server\Protocol\Message;

class Exit_ implements MessageHandlerInterface
{
    private bool $wasShutdown = false;

    public function __invoke(Message $message, callable $next)
    {
        if (true === $this->wasShutdown) {
            if ('exit' !== $message->method) {
                throw new InvalidRequestException();
            }

            $this->exit();
        }

        if ('shutdown' === $message->method) {
            $this->wasShutdown = true;
        }

        return $next->__invoke($message);
    }

    private function exit(): void
    {
        exit();
    }
}
