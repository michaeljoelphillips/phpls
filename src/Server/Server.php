<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use Psr\Log\LoggerInterface;
use React\Promise\Promise;
use React\Socket\ConnectionInterface;
use React\Socket\Server as TcpServer;
use React\Stream\DuplexStreamInterface;
use Throwable;

use function sprintf;

class Server
{
    private DuplexStreamInterface $stream;
    private MessageSerializer $serializer;
    private MessageParser $parser;
    private LoggerInterface $logger;

    /** @var callable */
    private $handler;

    /**
     * @param array<int, MessageHandler> $handlers
     */
    public function __construct(MessageSerializer $serializer, LoggerInterface $logger, array $handlers)
    {
        $this->serializer = $serializer;
        $this->logger     = $logger;
        $this->parser     = new MessageParser($serializer);

        $this->handler = function (Message $message, int $position) use ($handlers) {
            if ($message instanceof ResponseMessage) {
                return $message;
            }

            if (isset($handlers[$position + 1]) === false) {
                return $handlers[$position]->__invoke($message, static function ($response) {
                    return $response;
                });
            }

            $next = function (Message $message) use ($position) {
                $handler = $this->handler;

                return $handler($message, $position + 1);
            };

            return $handlers[$position]->__invoke($message, $next);
        };

        $this->parser->on('message', function (Message $request): void {
            $this->handle($request);
        });
    }

    /**
     * @param TcpServer|DuplexStreamInterface|Promise $stream
     */
    public function listen($stream): void
    {
        if ($stream instanceof TcpServer) {
            $stream->on('connection', function (ConnectionInterface $connection): void {
                $this->listen($connection);
            });

            return;
        }

        if ($stream instanceof Promise) {
            $stream->then(function (ConnectionInterface $connection): void {
                $this->listen($connection);
            });

            return;
        }

        if ($stream instanceof DuplexStreamInterface) {
            $this->logger->info('Connected to the client');

            $this->stream = $stream;

            $stream->on('data', function (string $data): void {
                $this->parser->handle($data);
            });

            $stream->on('close', function (): void {
                $this->logger->critical('The connection to the client has closed unexpectedly');

                exit;
            });

            return;
        }
    }

    private function handle(Message $message): void
    {
        $this->logger->notice(sprintf('Received %s request', $message->method));

        try {
            $handler  = $this->handler;
            $response = $handler($message, 0);
        } catch (Throwable $t) {
            $this->logger->error($t->getMessage());

            $response = new ResponseMessage($message, $t);
        }

        if ($response === null) {
            return;
        }

        $response = $this->serializer->serialize($response);

        $this->stream->write($response);
    }
}
