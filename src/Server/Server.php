<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use InvalidArgumentException;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use React\Socket\ConnectionInterface;
use React\Socket\Server as TcpServer;
use React\Stream\DuplexStreamInterface;
use Throwable;

class Server
{
    private DuplexStreamInterface $stream;
    private MessageSerializer $serializer;
    private MessageParser $parser;

    /** @var callable */
    private $handler;

    /**
     * @param array<int, MessageHandler> $handlers
     */
    public function __construct(MessageSerializer $serializer, array $handlers)
    {
        $this->serializer = $serializer;
        $this->parser     = new MessageParser($serializer);

        $this->handler = function (Message $message, int $position) use ($handlers) {
            if ($message instanceof ResponseMessage || $message === null) {
                return $message;
            }

            if (isset($handlers[$position + 1]) === false) {
                return $handlers[$position]->__invoke($message, static function ($response) {
                    return $response;
                });
            }

            $next = function (Message $message) use ($position) {
                return $this->handler->__invoke($message, $position + 1);
            };

            return $handlers[$position]->__invoke($message, $next);
        };

        $this->parser->on('message', function (Message $request) : void {
            $this->handle($request);
        });
    }

    /**
     * @param TcpServer|DuplexStreamInterface $stream
     */
    public function listen($stream) : void
    {
        if ($stream instanceof TcpServer) {
            $stream->on('connection', function (ConnectionInterface $connection) : void {
                $this->listen($connection);
            });

            return;
        }

        if ($stream instanceof DuplexStreamInterface) {
            $this->stream = $stream;

            $stream->on('data', fn (string $data) => $this->parser->handle($data));

            return;
        }

        throw new InvalidArgumentException();
    }

    private function handle(Message $message) : void
    {
        try {
            $response = $this->handler->__invoke($message, 0);
        } catch (Throwable $e) {
            $response = new ResponseMessage($message, $e);
        }

        if ($response === null) {
            return;
        }

        $response = $this->serializer->serialize($response);

        $this->stream->write($response);
    }
}
