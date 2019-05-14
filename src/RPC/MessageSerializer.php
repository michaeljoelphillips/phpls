<?php

declare(strict_types=1);

namespace LanguageServer\RPC;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
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

    public function deserialize(string $request)
    {
        $requestMessage = $this->serializer->deserialize($request, RequestMessage::class, 'jsonrpc');

        $this->emit('deserialize', [$requestMessage]);
    }

    public function serialize(int $messageId, object $response)
    {
        $responseBody = $this->serializer->serialize($response, 'jsonrpc', ['messageId' => $messageId]);

        $this->emit('serialize', [$responseBody]);
    }
}
