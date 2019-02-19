<?php

declare(strict_types=1);

namespace LanguageServer\RPC;

use Evenement\EventEmitter;
use React\Stream\WritableStreamInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Server extends EventEmitter
{
    /**
     * The RPC request content terminator.
     *
     * @see https://microsoft.github.io/language-server-protocol/specification
     */
    protected const HEADER_TERMINATOR = "\r\n\r\n";

    /**
     * Handle data events from the stream.
     *
     * @param string                  $request
     * @param WritableStreamInterface $connection
     */
    protected function handle(string $request, WritableStreamInterface $connection)
    {
        $request = $this->makeRequest($request);

        $this->emit($request->method, [$request, $connection]);
    }

    /**
     * Create a Requst object from incoming request data.
     *
     * @param string $request
     *
     * @return Request
     */
    protected function makeRequest(string $request): object
    {
        [$headers, $content] = explode(self::HEADER_TERMINATOR, $request);

        $content = json_decode($content);

        return $content;
    }
}
