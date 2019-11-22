<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use LanguageServer\Exception\InvalidRequestException;
use LanguageServer\Method\NotificationHandlerInterface;
use LanguageServer\Method\RemoteMethodInterface;
use LanguageServer\Server\Protocol\RequestMessage;
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
    private $serializer;
    private $shutdownRequestReceived = false;


    public function __construct(ContainerInterface $container, MessageSerializer $serializer, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function listen(DuplexStreamInterface $stream): void
    {
        $this->serializer->on('deserialize', function (RequestMessage $request) {
            if ($this->shutdownRequestReceived === true) {
                $this->sendInvalidRequestError($request);

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
        });

        $this->serializer->on('serialize', function (string $response) use ($stream) {
            $this->logger->debug('Sending response', [$response]);

            $stream->write($response);
        });

        $stream->on('data', function (string $request) {
            $this->logger->debug('Received request', [$request]);

            $this->serializer->deserialize($request);
        });
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

    public function shutdown(): void
    {
        $this->shutdownRequestReceived = true;
    }
}
