<?php

declare(strict_types=1);

namespace LanguageServer\Server\Cache;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use React\EventLoop\LoopInterface;
use function ini_get;
use function memory_get_usage;
use function sprintf;
use function strtoupper;

class ThresholdCacheMonitor implements CacheMonitor
{
    private const MEMORY_USAGE_THRESHOLD = 0.9;
    private const MEMORY_SIZE_MAP        = [
        'K' => 1,
        'M' => 2,
        'G' => 3,
    ];

    private LoggerInterface $logger;

    /** @var CacheInterface[] */
    private array $caches;

    private int $memoryLimit;

    public function __construct(LoggerInterface $logger, CacheInterface ...$caches)
    {
        $this->logger      = $logger;
        $this->caches      = $caches;
        $this->memoryLimit = self::getMemoryLimit();
    }

    private static function getMemoryLimit() : int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return (int) $memoryLimit;
        }

        return (int) $memoryLimit * 1024 ** self::MEMORY_SIZE_MAP[strtoupper($memoryLimit[-1])];
    }

    public function __invoke(int $interval, LoopInterface $loop) : void
    {
        $loop->addPeriodicTimer($interval, function () : void {
            $this->logger->debug('Theshold timer invoked');

            if ($this->memoryExceedsThreshold() === false) {
                return;
            }

            $this->clearCache();
        });
    }

    private function memoryExceedsThreshold() : bool
    {
        if ($this->memoryLimit === -1) {
            return false;
        }

        $percentUsed = memory_get_usage() / $this->memoryLimit;

        $this->logger->debug(sprintf('Percentage of total memory used: %f', $percentUsed));

        return $percentUsed > self::MEMORY_USAGE_THRESHOLD;
    }

    private function clearCache() : void
    {
        $this->logger->debug(sprintf('Threshold Cache Monitor: Memory Usage Before: %d', memory_get_usage()));

        foreach ($this->caches as $cache) {
            $cache->clear();
        }

        $this->logger->debug(sprintf('Threshold Cache Monitor: Memory Usage After: %d', memory_get_usage()));
    }
}
