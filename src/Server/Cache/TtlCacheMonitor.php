<?php

declare(strict_types=1);

namespace LanguageServer\Server\Cache;

use React\EventLoop\LoopInterface;

class TtlCacheMonitor implements CacheMonitor
{
    /** @var CleanableCache[] */
    private array $caches;

    public function __construct(CleanableCache ...$caches)
    {
        $this->caches = $caches;
    }

    public function __invoke(int $interval, LoopInterface $loop) : void
    {
        $loop->addPeriodicTimer($interval, function () : void {
            foreach ($this->caches as $cache) {
                $cache->clean();
            }
        });
    }
}
