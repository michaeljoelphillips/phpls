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
    private $serializer;
    private $stream;

    public function __construct(ContainerInterface $container, MessageSerializer $serializer, LoggerInterface $logger, DuplexStreamInterface $stream)
    {
        $this->container = $container;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->stream = $stream;
    }

    public function start(): void
    {
        $this->attachStreamEventListeners();

        $this->read([$this, 'waitForInitialization']);
    }

    private function attachStreamEventListeners(): void
    {
        $this->stream->on('data', [$this->serializer, 'deserialize']);
        $this->serializer->on('serialize', [$this->stream, 'write']);
    }

    private function read(callable $callback): void
    {
        $this->serializer->removeAllListeners('deserialize');

        $this->serializer->on('deserialize', Closure::fromCallable($callback));
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

        $this->read([$this, 'handleRequest']);
    }

    public function waitForExit(RequestMessage $request): void
    {
        if ($request->method !== 'exit') {
            $this->sendInvalidRequestError($request);

            return;
        }

        exit;
    }

    public function handleRequest(RequestMessage $request)
    {
        if ($request->method === 'shutdown') {
            $this->shutdown($request);

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

        $this->serializer->serialize(new ResponseMessage($request, $result));
    }

    private function shutdown(RequestMessage $request): void
    {
        $this->serializer->serialize(new ResponseMessage($request, null));

        $this->read([$this, 'waitForExit']);
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

        $this->serializer->serialize(new ResponseMessage($request, new ServerNotInitializedException()));
    }

    private function sendInvalidRequestError(RequestMessage $request): void
    {
        $this->logger->error('The client sent a request to the server after the server was shutdown');

        $this->serializer->serialize(new ResponseMessage($request, new InvalidRequestException()));
    }

    public function sendMethodNotFoundError(RequestMessage $request): void
    {
        $this->logger->error(sprintf('Method %s could not be located', $request->method));

        $this->serializer->serialize(new ResponseMessage($request, new InvalidRequestException()));
    }
}
