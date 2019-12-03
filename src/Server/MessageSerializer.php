<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class MessageSerializer implements EventEmitterInterface
{
    use EventEmitterTrait;

    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    public function __construct(SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function deserialize(string $request): void
    {
        $this->logger->debug($request);

        try {
            $request = $this->serializer->deserialize($request, Message::class, 'jsonrpc');

            $this->emit('deserialize', [$request]);
        } catch (NotEncodableValueException $e) {
            return;
        }
    }

    public function serialize(ResponseMessage $response): void
    {
        try {
            $response = $this->serializer->serialize($response, 'jsonrpc');

            $this->emit('serialize', [$response]);
        } catch (NotEncodableValueException $e) {
            return;
        }
    }
}
