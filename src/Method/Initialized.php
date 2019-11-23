<?php

declare(strict_types=1);

namespace LanguageServer\Method;

use LanguageServer\Server\Protocol\Message;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Initialized implements RequestHandlerInterface, NotificationHandlerInterface
{
    public function __invoke(Message $request)
    {
    }
}
