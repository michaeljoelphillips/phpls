<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use PhpParser\Error;
use PhpParser\ErrorHandler;
use PhpParser\ErrorHandler\Collecting;
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
            [$nodes, $errors] = $this->cache->get($hash);

            $this->handleErrors($errorHandler, $errors);

            return $nodes;
        }

        $localHandler = new Collecting();
        $nodes        = $this->wrappedParser->parse($code, $localHandler);
        $errors       = $localHandler->getErrors();

        $this->cache->set($hash, [$nodes, $errors]);
        $this->handleErrors($errorHandler, $errors);

        return $nodes;
    }

    /**
     * @param array<int, Error> $errors
     */
    private function handleErrors(?ErrorHandler $handler, array $errors): void
    {
        if ($handler === null) {
            return;
        }

        foreach ($errors as $error) {
            $handler->handleError($error);
        }
    }
}
