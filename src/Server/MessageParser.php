<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\MessageSerializerInterface;
use LanguageServer\Server\Protocol\ResponseMessage;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use React\Stream\ReadableStreamInterface;

class MessageParser implements EventEmitterInterface
{
    use EventEmitterTrait;

    private const HEADER_TERMINATOR = "\r\n\r\n";

    private string $buffer = '';
    private ReadableStreamInterface $stream;
    private MessageSerializerInterface $wrappedSerializer;

    public function __construct(MessageSerializerInterface $wrappedSerializer)
    {
        $this->wrappedSerializer = $wrappedSerializer;
    }

    public function handle(string $message)
    {
        $this->buffer .= $message;

        while ($this->bufferContainsCompleteMessage() !== false) {
            $message = $this->readMessageFromBuffer();

            $this->trimBuffer();

            if ($message !== null) {
                $this->emit('message', [$this->wrappedSerializer->deserialize($message)]);
            }
        }
    }

    private function bufferContainsCompleteMessage(): bool
    {
        $terminatorPosition = $this->findHeaderTerminator();

        if ($terminatorPosition === false) {
            return false;
        }

        $messageLength = $this->findContentLength($terminatorPosition);

        if (strlen($this->buffer) < $terminatorPosition + $messageLength) {
            return false;
        }

        return true;
    }

    private function findHeaderTerminator()
    {
        return strpos($this->buffer, self::HEADER_TERMINATOR);
    }

    private function parseBufferedMessage(int $terminatorPosition): array
    {
        $length = $this->findContentLength($terminatorPosition);

        return [$length];
    }

    private function findContentLength(int $terminatorPosition): int
    {
        $headers = explode("\r\n", substr($this->buffer, 0, $terminatorPosition));

        $contentLength = array_filter($headers, fn (string $header) => strpos($header, 'Content-Length') !== false);

        [$_, $contentLength] = explode(':', array_pop($contentLength));

        return (int) trim($contentLength);
    }

    private function readMessageFromBuffer(): string
    {
        $terminatorPosition = $this->findHeaderTerminator();
        $messageLength = $this->findContentLength($terminatorPosition);

        return substr($this->buffer, 0, $terminatorPosition + strlen(self::HEADER_TERMINATOR) + $messageLength);
    }

    private function trimBuffer(): void
    {
        $terminatorPosition = $this->findHeaderTerminator();
        $messageLength = $this->findContentLength($terminatorPosition);

        $this->buffer = substr($this->buffer, $terminatorPosition + strlen(self::HEADER_TERMINATOR) + $messageLength);
    }
}
