<?php

declare(strict_types=1);

namespace LanguageServer\RPC;

use Evenement\EventEmitter;
use React\Socket\ServerInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Server extends EventEmitter
{
    protected const HEADER_TERMINATOR = PHP_EOL.PHP_EOL;

    public static function fromStdio(ReadableStreamInterface $input, WritableStreamInterface $output)
    {
        $instance = new self();

        $input->on(
            'data',
            function (string $data) use ($output, $instance) {
                $instance->handleRequest($data, $output);
            }
        );

        return $instance;
    }

    public static function fromServer(ServerInterface $server)
    {
        $instance = new self();

        $server->on(
            'connection',
            function (WritableStreamInterface $connection) use ($instance) {
                $connection->on(
                    'data',
                    function (string $data) use ($connection, $instance) {
                        $instance->handleRequest($data, $connection);
                    }
                );
            }
        );

        return $instance;
    }

    private function __construct()
    {
    }

    protected function handleRequest(string $request, WritableStreamInterface $connection)
    {
        $request = $this->makeRequest($request);

        $this->emit($request->getMethod(), [$request, $connection]);
    }

    private function makeRequest(string $request): Request
    {
        [$headers, $body] = explode(self::HEADER_TERMINATOR, $request);

        $body = json_decode($body);

        return new Request($body->id, $body->method, $body->params);
    }
}
