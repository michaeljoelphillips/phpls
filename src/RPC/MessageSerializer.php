<?php

declare(strict_types=1);

namespace LanguageServer\RPC;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class MessageSerializer implements EventEmitterInterface
{
    use EventEmitterTrait;

    /** @var SerializerInterface */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function deserialize(string $request): void
    {
        try {
            $requestMessage = $this->serializer->deserialize($request, RequestMessage::class, 'jsonrpc');

            $this->emit('deserialize', [$requestMessage]);
        } catch (NotEncodableValueException $e) {
            return;
        }
    }

    public function serialize(int $messageId, object $response): void
    {
        try {
            $responseBody = $this->serializer->serialize($response, 'jsonrpc', ['messageId' => $messageId]);

            $this->emit('serialize', [$responseBody]);
        } catch (NotEncodableValueException $e) {
            return;
        }
    }
}
