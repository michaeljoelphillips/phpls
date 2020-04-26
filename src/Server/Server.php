<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use InvalidArgumentException;
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
     * @param TcpServer|DuplexStreamInterface|Promise<DuplexStreamInterface> $stream
     */
    public function listen($stream) : void
    {
        if ($stream instanceof TcpServer) {
            $stream->on('connection', function (ConnectionInterface $connection) : void {
                $this->listen($connection);
            });

            return;
        }

        if ($stream instanceof Promise) {
            $stream->then(function (ConnectionInterface $connection) : void {
                $this->listen($connection);
            });

            return;
        }

        if ($stream instanceof DuplexStreamInterface) {
            $this->logger->info('Initializing server');

            $this->stream = $stream;

            $stream->on('data', fn (string $data) => $this->parser->handle($data));

            return;
        }

        throw new InvalidArgumentException();
    }

    /**
     * @param NotificationMessage|RequestMessage $message
     */
    private function handle(Message $message) : void
    {
        $this->logger->debug(sprintf('Received %s request', $message->method));

        try {
            $response = $this->handler->__invoke($message, 0);
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
