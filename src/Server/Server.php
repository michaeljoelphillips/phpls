<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use LanguageServer\Exception\LanguageServerException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;
use Throwable;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Server
{
    private $container;
    private $serializer;
    private $logger;

    public function __construct(ContainerInterface $container, MessageSerializer $serializer, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function listen(ServerInterface $socket): void
    {
        $socket->on('connection', [$this, 'handleRequest']);
    }

    public function handleRequest(ConnectionInterface $connection): void
    {
        $this->serializer->on('deserialize', function (RequestMessage $request) {
            $result = $this->invokeRemoteMethod($request);

            if (null === $result) {
                return;
            }

            $response = $this->prepareResponse($result, $request);

            $this->serializer->serialize($response);
        });

        $this->serializer->on('serialize', function (string $response) use ($connection) {
            $connection->write($response);
        });

        $connection->on('data', [$this->serializer, 'deserialize']);
    }

    private function invokeRemoteMethod(RequestMessage $request): ?object
    {
        $this->logger->debug(sprintf('Received %s', $request->method));

        try {
            $object = $this->container->get($request->method);

            return $object->__invoke($request->params);
        } catch (Throwable $t) {
            $this->logger->error($t->getMessage());

            return $t;
        }
    }

    private function prepareResponse(object $result, RequestMessage $request): ResponseMessage
    {
        if ($result instanceof Throwable) {
            return ResponseMessage::createErrorResponse($result, $request->id);
        }

        return ResponseMessage::createSuccessfulResponse($result, $request->id);
    }
}
