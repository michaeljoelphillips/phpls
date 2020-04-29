<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use PhpParser\ErrorHandler;
use PhpParser\Parser;
use Psr\SimpleCache\CacheInterface;
use function hash;
use function strlen;

class MemoizingParser implements Parser
{
    private CacheInterface $cache;
    private Parser $wrappedParser;

    public function __construct(CacheInterface $cache, Parser $wrappedParser)
    {
        $this->cache         = $cache;
        $this->wrappedParser = $wrappedParser;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $code, ?ErrorHandler $errorHandler = null)
    {
        $hash = hash('sha256', $code) . ':' . strlen($code);

        if ($this->cache->has($hash)) {
            return $this->cache->get($hash);
        }

        $result = $this->wrappedParser->parse($code, $errorHandler);

        $this->cache->set($hash, $result);

        return $result;
    }
}
