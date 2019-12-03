<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use LanguageServer\Exception\LanguageServerException;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use React\Stream\DuplexStreamInterface;
use Throwable;

class JsonRpcServer implements RequestReaderInterface, ResponseWriterInterface
{
    private DuplexStreamInterface $stream;
    private MessageSerializer $serializer;

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

        $this->serializer->on(
            'deserialize',
            function (Message $message) use ($callback): void {
                try {
                    $callback($message);
                } catch (LanguageServerException $e) {
                    $this->serializer->serialize(new ResponseMessage($message, $e));
                } catch (Throwable $t) {
                    $this->serializer->serialize(new ResponseMessage($message, $t));
                }
            }
        );
    }

    public function write(ResponseMessage $response): void
    {
        $this->serializer->serialize($response);
    }
}
