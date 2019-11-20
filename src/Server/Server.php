<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\Stream\DuplexStreamInterface;
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

    public function listen(DuplexStreamInterface $stream): void
    {
        $this->serializer->on('deserialize', function (RequestMessage $request) {
            $result = $this->invokeRemoteMethod($request);

            if (null === $result) {
                return;
            }

            $response = $this->prepareResponse($result, $request);

            $this->serializer->serialize($response);
        });

        $this->serializer->on('serialize', function (string $response) use ($stream) {
            $this->logger->debug('Sending response', [$request]);

            $stream->write($response);
        });

        $stream->on('data', function (string $request) {
            $this->logger->debug('Received request', [$request]);

            $this->serializer->deserialize($request);
        });
    }

    private function invokeRemoteMethod(RequestMessage $request): ?object
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

    private function prepareResponse(object $result, RequestMessage $request): ResponseMessage
    {
        if ($result instanceof Throwable) {
            return ResponseMessage::createErrorResponse($result, $request->id);
        }

        return ResponseMessage::createSuccessfulResponse($result, $request->id);
    }
}
