<?php

namespace LanguageServer\Server;

use Closure;
use LanguageServer\Server\Protocol\ResponseMessage;
use LanguageServer\Server\RequestReaderInterface;
use LanguageServer\Server\ResponseWriterInterface;
use React\Stream\DuplexStreamInterface;

class JsonRpcServer implements RequestReaderInterface, ResponseWriterInterface
{
    private $stream;
    private $serializer;

    public function __construct(DuplexStreamInterface $stream, MessageSerializer $serializer)
    {
        $this->stream = $stream;
        $this->serializer = $serializer;

        $this->attachStreamEvents();
    }

    private function attachStreamEvents(): void
    {
        $this->stream->on('data', [$this->serializer, 'deserialize']);
        $this->serializer->on('serialize', [$this->stream, 'write']);
    }

    public function read(callable $callback): void
    {
        $this->serializer->removeAllListeners('deserialize');

        $this->serializer->on('deserialize', Closure::fromCallable($callback));
    }

    public function write(ResponseMessage $response): void
    {
        $this->serializer->serialize($response);
    }
}
