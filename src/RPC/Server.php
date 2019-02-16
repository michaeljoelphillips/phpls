<?php

declare(strict_types=1);

namespace LanguageServer\RPC;

use Evenement\EventEmitter;
use React\Stream\ReadableResourceStream;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Server extends EventEmitter
{
    protected const HEADER_TERMINATOR = PHP_EOL.PHP_EOL;

    public function __construct(ReadableResourceStream $stream)
    {
        $stream->on('data', [$this, 'handleRequest']);
    }

    protected function handleRequest(string $request)
    {
        $request = $this->makeRequest($request);

        $this->emit($request->getMethod(), [$request]);
    }

    private function makeRequest(string $request): Request
    {
        [$headers, $body] = explode(self::HEADER_TERMINATOR, $request);

        $body = json_decode($body);

        return new Request($body->id, $body->method, $body->params);
    }
}
