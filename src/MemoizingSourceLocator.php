<?php

declare(strict_types=1);

namespace LanguageServer;

use Psr\SimpleCache\CacheInterface;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use function spl_object_hash;
use function sprintf;

class MemoizingSourceLocator implements SourceLocator
{
    private CacheInterface $cache;
    private SourceLocator $wrappedLocator;

    public function __construct(CacheInterface $cache, SourceLocator $wrappedLocator)
    {
        $this->cache          = $cache;
        $this->wrappedLocator = $wrappedLocator;
    }

    public function locateIdentifier(Reflector $reflector, Identifier $identifier) : ?Reflection
    {
        $cacheKey = $this->getCacheKey($reflector, $identifier->getType(), $identifier);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $reflection = $this->wrappedLocator->locateIdentifier($reflector, $identifier);

        $this->cache->set($cacheKey, $reflection);

        return $reflection;
    }

    /**
     * {@inheritdoc}
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType) : array
    {
        $cacheKey = $this->getCacheKey($reflector, $identifierType, null);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $reflection = $this->wrappedLocator->locateIdentifiersByType($reflector, $identifierType);

        $this->cache->set($cacheKey, $reflection);

        return $reflection;
    }

    private function getCacheKey(Reflector $reflector, IdentifierType $identifierType, ?Identifier $identifier) : string
    {
        $key = sprintf(
            '%s:%s',
            spl_object_hash($reflector),
            $identifierType->getName()
        );

        if ($identifier !== null) {
            $key = sprintf('%s:%s', $key, $identifier->getName());
        }

        return $key;
    }
}
