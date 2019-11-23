<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use Closure;
use LanguageServer\Exception\InvalidRequestException;
use LanguageServer\Exception\ServerNotInitializedException;
use LanguageServer\Method\MessageHandlerInterface;
use LanguageServer\Method\NotificationHandlerInterface;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\Stream\DuplexStreamInterface;
use Throwable;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Server
{
    private $logger;
    private $container;
    private $messageReader;
    private $responseWriter;

    public function __construct(ContainerInterface $container, RequestReaderInterface $messageReader, ResponseWriterInterface $responseWriter, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->container = $container;
        $this->requestReader = $messageReader;
        $this->responseWriter = $responseWriter;
    }

    public function start(): void
    {
        $this->requestReader->read(Closure::fromCallable([$this, 'waitForInitialization']));
    }

    private function waitForInitialization(Message $message): void
    {
        if ($message->method === 'exit') {
            exit;
        }

        if ($message->method !== 'initialize') {
            $this->sendServerNotInitializedError();

            return;
        }

        $this->handleRequest($message);

        $this->requestReader->read(Closure::fromCallable([$this, 'handleRequest']));
    }

    public function waitForExit(Message $message): void
    {
        if ($message->method !== 'exit') {
            $this->sendInvalidRequestError($message);

            return;
        }

        $this->exit();
    }

    private function exit(): void
    {
        exit();
    }

    public function handleRequest(Message $message): void
    {
        if ($message->method === 'shutdown') {
            $this->shutdown($message);

            return;
        }

        if ($message->method === 'exit') {
            $this->exit();

            return;
        }

        if ($this->container->has($message->method) === false) {
            $this->sendMethodNotFoundError($message);

            return;
        }

        $method = $this->container->get($message->method);
        $response = $this->invokeMethod($method, $message);

        if ($method instanceof NotificationHandlerInterface) {
            return;
        }

        $this->responseWriter->write($response);
    }

    private function shutdown(Message $message): void
    {
        $this->responseWriter->write(new ResponseMessage($message, null));

        $this->requestReader->read(Closure::fromCallable([$this, 'waitForExit']));
    }

    private function invokeMethod(MessageHandlerInterface $method, Message $message): ?object
    {
        $this->logger->info(sprintf('Invoking method %s', $message->method));

        try {
            $method = $this->container->get($message->method);

            return $method->__invoke($message);
        } catch (Throwable $t) {
            $this->logger->error(sprintf('%s: %s', get_class($t), $t->getMessage()));

            return $t;
        }
    }

    private function sendServerNotInitializedError(Message $message): void
    {
        $this->logger->error('The client sent a message to the server before the server was initialized');

        $this->responseWriter->write(new ResponseMessage($message, new ServerNotInitializedException()));
    }

    private function sendInvalidRequestError(Message $message): void
    {
        $this->logger->error('The client sent a message to the server after the server was shutdown');

        $this->responseWriter->write(new ResponseMessage($message, new InvalidRequestException()));
    }

    public function sendMethodNotFoundError(Message $message): void
    {
        $this->logger->error(sprintf('Method %s could not be located', $message->method));

        $this->responseWriter->write(new ResponseMessage($message, new InvalidRequestException()));
    }
}
