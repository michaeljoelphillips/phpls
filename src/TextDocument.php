<?php

declare(strict_types=1);

namespace LanguageServer;

use function array_splice;
use function explode;
use function implode;
use function strlen;
use function substr;

class TextDocument
{
    private string $uri;
    private string $source;
    private int $version;

    public function __construct(string $uri, string $source, int $version)
    {
        $this->uri     = $uri;
        $this->source  = $source;
        $this->version = $version;
    }

    public function getUri() : string
    {
        return $this->uri;
    }

    public function getSource() : string
    {
        return $this->source;
    }

    public function getVersion() : int
    {
        return $this->version;
    }

    /**
     * Calculate the cursor position relative to the beginning of the file.
     *
     * This method removes all characters proceeding the $character at $line
     * and counts the total length of the final string.
     */
    public function getCursorPosition(int $line, int $character) : CursorPosition
    {
        $lines            = explode("\n", $this->source);
        $lines            = array_splice($lines, 0, $line);
        $lines[$line - 1] = substr($lines[$line - 1], 0, $character);
        $lines            = implode("\n", $lines);

        $relativePosition = strlen($lines);

        return new CursorPosition($line, $character, $relativePosition);
    }
}
