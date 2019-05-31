<?php

declare(strict_types=1);

namespace LanguageServer;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TextDocument
{
    private $uri;
    private $source;
    private $version;

    public function __construct(string $uri, string $source, int $version)
    {
        $this->uri = $uri;
        $this->source = $source;
        $this->version = $version;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Calculate the cursor position relative to the beginning of the file.
     *
     * This method removes all characters proceeding the $character at $line
     * and counts the total length of the final string.
     *
     * @param int $line
     * @param int $character
     *
     * @return CursorPosition
     */
    public function getCursorPosition(int $line, int $character): CursorPosition
    {
        $lines = explode(PHP_EOL, $this->source);
        $lines = array_splice($lines, 0, $line);
        $lines[$line - 1] = substr($lines[$line - 1], 0, $character);
        $lines = implode(PHP_EOL, $lines);

        $relativePosition = strlen($lines);

        return new CursorPosition($line, $character, $relativePosition);
    }
}
