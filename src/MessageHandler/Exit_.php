<?php

declare(strict_types=1);

namespace LanguageServer\MessageHandler;

use LanguageServer\Server\Exception\InvalidRequest;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;

// phpcs:ignore
class Exit_ implements MessageHandler
{
    private bool $wasShutdown = false;

    /**
     * @return mixed
     */
    public function __invoke(Message $message, callable $next)
    {
        if ($this->wasShutdown === true) {
            if ($message->method !== 'exit') {
                throw new InvalidRequest();
            }

            $this->exit();
        }

        if ($message->method === 'shutdown') {
            $this->wasShutdown = true;

            return new ResponseMessage($message, null);
        }

        return $next->__invoke($message);
    }

    private function exit(): void
    {
        exit;
    }
}
