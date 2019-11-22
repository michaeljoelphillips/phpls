<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use LanguageServer\Exception\InvalidRequestException;
use LanguageServer\Exception\ServerNotInitializedException;
use LanguageServer\Method\NotificationHandlerInterface;
use LanguageServer\Method\RemoteMethodInterface;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Server\Protocol\ResponseMessage;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\Stream\DuplexStreamInterface;
use Throwable;
use Closure;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Server
{
    private $logger;
    private $container;
    private $requestReader;
    private $responseWriter;

    public function __construct(ContainerInterface $container, RequestReaderInterface $requestReader, ResponseWriterInterface $responseWriter, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->container = $container;
        $this->requestReader = $requestReader;
        $this->responseWriter = $responseWriter;
    }

    public function start(): void
    {
        $this->requestReader->read(Closure::fromCallable([$this, 'waitForInitialization']));
    }

    private function waitForInitialization(RequestMessage $request): void
    {
        if ($request->method === 'exit') {
            exit;
        }

        if ($request->method !== 'initialize') {
            $this->sendServerNotInitializedError();

            return;
        }

        $this->handleRequest($request);

        $this->requestReader->read(Closure::fromCallable([$this, 'handleRequest']));
    }

    public function waitForExit(RequestMessage $request): void
    {
        if ($request->method !== 'exit') {
            $this->sendInvalidRequestError($request);

            return;
        }

        $this->exit();
    }

    private function exit(): void
    {
        exit();
    }

    public function handleRequest(RequestMessage $request)
    {
        if ($request->method === 'shutdown') {
            $this->shutdown($request);

            return;
        }

        if ($request->method === 'exit') {
            $this->exit();

            return;
        }

        if ($this->container->has($request->method) === false) {
            $this->sendMethodNotFoundError($request);

            return;
        }

        $method = $this->container->get($request->method);
        $result = $this->invokeMethod($method, $request);

        if ($method instanceof NotificationHandlerInterface) {
            return;
        }

        $this->responseWriter->write(new ResponseMessage($request, $result));
    }

    private function shutdown(RequestMessage $request): void
    {
        $this->responseWriter->write(new ResponseMessage($request, null));

        $this->requestReader->read(Closure::fromCallable([$this, 'waitForExit']));
    }

    private function invokeMethod(RemoteMethodInterface $method, RequestMessage $request): ?object
    {
        $this->logger->info(sprintf('Invoking method %s', $request->method));

        try {
            $method = $this->container->get($request->method);

            return $method->__invoke($request->params ?? []);
        } catch (Throwable $t) {
            $this->logger->error(sprintf('%s: %s', get_class($t), $t->getMessage()));

            return $t;
        }
    }

    private function sendServerNotInitializedError(RequestMessage $request): void
    {
        $this->logger->error('The client sent a request to the server before the server was initialized');

        $this->responseWriter->write(new ResponseMessage($request, new ServerNotInitializedException()));
    }

    private function sendInvalidRequestError(RequestMessage $request): void
    {
        $this->logger->error('The client sent a request to the server after the server was shutdown');

        $this->responseWriter->write(new ResponseMessage($request, new InvalidRequestException()));
    }

    public function sendMethodNotFoundError(RequestMessage $request): void
    {
        $this->logger->error(sprintf('Method %s could not be located', $request->method));

        $this->responseWriter->write(new ResponseMessage($request, new InvalidRequestException()));
    }
}
