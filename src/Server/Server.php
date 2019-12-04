<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use React\Stream\DuplexStreamInterface;

class Server
{
    private DuplexStreamInterface $stream;
    private MessageSerializer $serializer;
    private $handler;

    public function __construct(MessageSerializer $serializer, array $handlers)
    {
        $this->serializer = $serializer;

        $this->handler = function (Message $message, int $position) use ($handlers) {
            if ($message instanceof ResponseMessage) {
                return $message;
            }

            if (false === isset($handlers[$position + 1])) {
                return $handlers[$position]->__invoke($message, function ($response) {
                    return $response;
                });
            }

            $next = function (Message $message) use ($position) {
                return $this->handler->__invoke($message, $position + 1);
            };

            return $handlers[$position]->__invoke($message, $next);
        };
    }

    public function listen(DuplexStreamInterface $stream): void
    {
        $this->stream = $stream;

        $stream->on('data', function (string $message) {
            $this->handle($message);
        });
    }

    private function handle(string $request): void
    {
        $message = $this->serializer->deserialize($request);

        if ($message === null) {
            return;
        }

        try {
            $response = $this->handler->__invoke($message, 0);
        } catch (Throwable $e) {
            $response = new ResponseMessage($message, $e);
        }

        if ($response === null) {
            return;
        }

        $this->stream->write($this->serializer->serialize($response));
    }
}
