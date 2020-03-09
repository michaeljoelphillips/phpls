<?php

declare(strict_types=1);

namespace LanguageServer\Server\Serializer;

use LanguageServer\Server\MessageSerializer as MessageSerializerInterface;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

class MessageSerializer implements MessageSerializerInterface
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function deserialize(string $request) : ?Message
    {
        try {
            return $this->serializer->deserialize($request, Message::class, 'jsonrpc');
        } catch (NotEncodableValueException $e) {
            return null;
        }
    }

    public function serialize(ResponseMessage $response) : string
    {
        try {
            return $this->serializer->serialize($response, 'jsonrpc');
        } catch (NotEncodableValueException $e) {
            return '{}';
        }
    }
}
