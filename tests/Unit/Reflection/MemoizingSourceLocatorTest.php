<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Reflection;

use LanguageServer\Reflection\MemoizingSourceLocator;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class MemoizingSourceLocatorTest extends TestCase
{
    public function testLocateIdentifierWithCacheMiss(): void
    {
        $cache   = $this->createMock(CacheInterface::class);
        $locator = $this->createMock(SourceLocator::class);
        $subject = new MemoizingSourceLocator($cache, $locator);

        $reflector  = $this->createMock(Reflector::class);
        $identifier = new Identifier('Test', new IdentifierType());

        $cache
            ->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $cache
            ->expects($this->once())
            ->method('set');

        $locator
            ->expects($this->once())
            ->method('locateIdentifier');

        $subject->locateIdentifier($reflector, $identifier);
    }

    public function testLocateIdentifierWithCacheHit(): void
    {
        $cache   = $this->createMock(CacheInterface::class);
        $locator = $this->createMock(SourceLocator::class);
        $subject = new MemoizingSourceLocator($cache, $locator);

        $reflector  = $this->createMock(Reflector::class);
        $identifier = new Identifier('Test', new IdentifierType());

        $cache
            ->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $cache
            ->expects($this->once())
            ->method('get');

        $cache
            ->expects($this->never())
            ->method('set');

        $locator
            ->expects($this->never())
            ->method('locateIdentifier');

        $subject->locateIdentifier($reflector, $identifier);
    }

    public function testLocateIdentifierByTypeWithCacheMiss(): void
    {
        $cache   = $this->createMock(CacheInterface::class);
        $locator = $this->createMock(SourceLocator::class);
        $subject = new MemoizingSourceLocator($cache, $locator);

        $reflector      = $this->createMock(Reflector::class);
        $identifierType = new IdentifierType();

        $reflections = [
            $this->createMock(Reflection::class),
            $this->createMock(Reflection::class),
        ];

        $cache
            ->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $cache
            ->expects($this->once())
            ->method('set');

        $locator
            ->expects($this->once())
            ->method('locateIdentifiersByType')
            ->with($reflector, $identifierType)
            ->willReturn($reflections);

        $result = $subject->locateIdentifiersByType($reflector, $identifierType);

        $this->assertEquals($reflections, $result);
    }

    public function testLocateIdentifierByTypeWithCacheHit(): void
    {
        $cache   = $this->createMock(CacheInterface::class);
        $locator = $this->createMock(SourceLocator::class);
        $subject = new MemoizingSourceLocator($cache, $locator);

        $reflector      = $this->createMock(Reflector::class);
        $identifierType = new IdentifierType();

        $cache
            ->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $cache
            ->method('get')
            ->willReturn([]);

        $cache
            ->expects($this->never())
            ->method('set');

        $locator
            ->expects($this->never())
            ->method('locateIdentifiersByType');

        $subject->locateIdentifiersByType($reflector, $identifierType);
    }
}
