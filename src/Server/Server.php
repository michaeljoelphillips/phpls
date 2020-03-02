<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use React\Stream\DuplexStreamInterface;
use Psr\Log\LoggerInterface;
use LanguageServer\Server\MessageParser;
use LanguageServer\Server\Protocol\RequestMessage;
use Throwable;
use React\Socket\ServerInterface;
use React\Socket\ConnectionInterface;
use InvalidArgumentException;
use React\Socket\Server as TcpServer;

class Server
{
    private DuplexStreamInterface $stream;
    private MessageSerializerInterface $serializer;
    private LoggerInterface $logger;
    private MessageParser $parser;
    private $handler;

    public function __construct(MessageSerializerInterface $serializer, LoggerInterface $logger, array $handlers)
    {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->parser = new MessageParser($serializer);

        $this->handler = function (Message $message, int $position) use ($handlers) {
            if ($message instanceof ResponseMessage || null === $message) {
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

        $this->parser->on('message', function (Message $request) {
            $this->handle($request);
        });
    }

    public function listen($stream): void
    {
        if ($stream instanceof TcpServer) {
            $stream->on('connection', function (ConnectionInterface $connection) {
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

    private function handle(Message $message): void
    {
        try {
            $response = $this->handler->__invoke($message, 0);
        } catch (Throwable $e) {
            $response = new ResponseMessage($message, $e);
        }

        if (null === $response) {
            return;
        }

        $response = $this->serializer->serialize($response);

        $this->stream->write($response);
    }
}
