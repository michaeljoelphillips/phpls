<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use LanguageServer\Server\Protocol\Message;

interface MessageHandler
{
    /**
     * @return mixed
     */
    public function __invoke(Message $request, callable $next);
}
