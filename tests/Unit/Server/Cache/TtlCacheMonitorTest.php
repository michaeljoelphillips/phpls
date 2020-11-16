<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Server\Cache;

use LanguageServer\Server\Cache\CleanableCache;
use LanguageServer\Server\Cache\TtlCacheMonitor;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;

class TtlCacheMonitorTest extends TestCase
{
    public function testMonitorInvokesCacheCleanOnTimer(): void
    {
        $cache = $this->createMock(CleanableCache::class);
        $loop  = $this->createMock(LoopInterface::class);

        $subject = new TtlCacheMonitor($cache);

        $cache
            ->expects($this->once())
            ->method('clean');

        $loop
            ->expects($this->once())
            ->method('addPeriodicTimer')
            ->with(
                10,
                $this->callback(
                    static function (callable $function) {
                        $function();

                        return true;
                    }
                )
            );

        $subject(10, $loop);
    }
}
