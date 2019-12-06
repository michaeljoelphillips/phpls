<?php

declare(strict_types=1);

namespace LanguageServer\Method;

use LanguageServer\Server\MessageHandlerInterface;
use LanguageServer\Server\Protocol\Message;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Initialized implements MessageHandlerInterface
{
    public function __invoke(Message $message, callable $next)
    {
        if ($message->method !== 'initialized') {
            return $next->__invoke($message);
        }

        return;
    }
}
