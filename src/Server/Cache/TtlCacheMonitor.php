<?php

declare(strict_types=1);

namespace LanguageServer\Server\Cache;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use function memory_get_usage;
use function sprintf;

class TtlCacheMonitor implements CacheMonitor
{
    private LoggerInterface $logger;

    /** @var CleanableCache[] */
    private array $caches;

    public function __construct(LoggerInterface $logger, CleanableCache ...$caches)
    {
        $this->logger = $logger;
        $this->caches = $caches;
    }

    public function __invoke(int $interval, LoopInterface $loop) : void
    {
        $loop->addPeriodicTimer($interval, function () : void {
            $this->logger->debug(sprintf('TTL Cache Monitor: Memory Usage Before: %d', memory_get_usage()));

            foreach ($this->caches as $cache) {
                $cache->clean();
            }

            $this->logger->debug(sprintf('TTL Cache Monitor: Memory Usage After: %d', memory_get_usage()));
        });
    }
}
