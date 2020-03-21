<?php

declare(strict_types=1);

namespace LanguageServer\Server\Cache;

use React\EventLoop\LoopInterface;

interface CacheMonitor
{
    public function __invoke(int $interval, LoopInterface $loop) : void;
}
