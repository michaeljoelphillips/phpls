<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;

use function array_filter;
use function array_pop;
use function assert;
use function explode;
use function is_int;
use function strlen;
use function strpos;
use function substr;
use function trim;

class MessageParser implements EventEmitterInterface
{
    use EventEmitterTrait;

    private const HEADER_TERMINATOR = "\r\n\r\n";

    private string $buffer = '';
    private MessageSerializer $wrappedSerializer;

    public function __construct(MessageSerializer $wrappedSerializer)
    {
        $this->wrappedSerializer = $wrappedSerializer;
    }

    public function handle(string $message): void
    {
        $this->buffer .= $message;

        while ($this->bufferContainsCompleteMessage() !== false) {
            $message = $this->readMessageFromBuffer();

            $this->trimBuffer();

            if (empty($message)) {
                continue;
            }

            $this->emit('message', [$this->wrappedSerializer->deserialize($message)]);
        }
    }

    private function bufferContainsCompleteMessage(): bool
    {
        $terminatorPosition = $this->findHeaderTerminator();

        if ($terminatorPosition === false) {
            return false;
        }

        $messageLength = $this->findContentLength($terminatorPosition);

        return strlen($this->buffer) >= $terminatorPosition + $messageLength;
    }

    private function readMessageFromBuffer(): string
    {
        $terminatorPosition = $this->findHeaderTerminator();
        assert(is_int($terminatorPosition));

        $messageLength = $this->findContentLength($terminatorPosition);

        return substr($this->buffer, 0, $terminatorPosition + strlen(self::HEADER_TERMINATOR) + $messageLength);
    }

    private function trimBuffer(): void
    {
        $terminatorPosition = $this->findHeaderTerminator();
        assert(is_int($terminatorPosition));

        $messageLength = $this->findContentLength($terminatorPosition);

        $this->buffer = substr($this->buffer, $terminatorPosition + strlen(self::HEADER_TERMINATOR) + $messageLength);
    }

    /**
     * @return int|false
     */
    private function findHeaderTerminator()
    {
        return strpos($this->buffer, self::HEADER_TERMINATOR);
    }

    private function findContentLength(int $terminatorPosition): int
    {
        $headers       = explode("\r\n", substr($this->buffer, 0, $terminatorPosition));
        $contentLength = array_filter($headers, static fn (string $header) => strpos($header, 'Content-Length') !== false);

        assert(empty($contentLength) === false);

        [$header, $contentLength] = explode(':', array_pop($contentLength));

        return (int) trim($contentLength);
    }
}
