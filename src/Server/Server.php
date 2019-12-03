<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use Closure;
use LanguageServer\Exception\InvalidRequestException;
use LanguageServer\Exception\ServerNotInitializedException;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Server
{
    private LoggerInterface $logger;
    private ContainerInterface $container;
    private RequestReaderInterface $messageReader;
    private ResponseWriterInterface $responseWriter;

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
        if ('exit' === $message->method) {
            exit;
        }

        if ('initialize' !== $message->method) {
            throw new ServerNotInitializedException();
        }

        $this->handleRequest($message);

        $this->requestReader->read(Closure::fromCallable([$this, 'handleRequest']));
    }

    public function waitForExit(Message $message): void
    {
        if ('exit' !== $message->method) {
            throw new InvalidRequestException();
        }

        $this->exit();
    }

    private function exit(): void
    {
        exit();
    }

    public function handleRequest(Message $message): void
    {
        if ('shutdown' === $message->method) {
            $this->shutdown($message);

            return;
        }

        if ('exit' === $message->method) {
            $this->exit();

            return;
        }

        if (false === $this->container->has($message->method)) {
            throw new InvalidRequestException();
        }

        $this->logger->info(sprintf('Invoking method %s', $message->method));

        $method = $this->container->get($message->method);
        $response = $method->__invoke($message);

        if (null === $response) {
            return;
        }

        $this->responseWriter->write($response);
    }

    private function shutdown(Message $message): void
    {
        $this->responseWriter->write(new ResponseMessage($message, null));

        $this->requestReader->read(Closure::fromCallable([$this, 'waitForExit']));
    }
}
