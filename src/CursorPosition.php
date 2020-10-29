<?php

declare(strict_types=1);

namespace LanguageServer;

use PhpParser\NodeAbstract;

class CursorPosition
{
    private int $line;
    private int $character;
    private int $relativePosition;

    public function __construct(int $line, int $character, int $relativePosition)
    {
        $this->line             = $line;
        $this->character        = $character;
        $this->relativePosition = $relativePosition;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getCharacter(): int
    {
        return $this->character;
    }

    public function getRelativePosition(): int
    {
        return $this->relativePosition;
    }

    public function contains(NodeAbstract $node): bool
    {
        return $this->isWithin($node) || $this->isSurrounding($node);
    }

    public function isSurrounding(NodeAbstract $node): bool
    {
        return $node->getStartFilePos() - 1 === $this->getRelativePosition()
            || $node->getEndFilePos() + 1 === $this->getRelativePosition();
    }

    public function isBordering(NodeAbstract $node): bool
    {
        return $node->getStartFilePos() === $this->getRelativePosition()
            || $node->getEndFilePos() === $this->getRelativePosition();
    }

    public function isWithin(NodeAbstract $node): bool
    {
        return $node->getStartFilePos() <= $this->getRelativePosition()
            && $node->getEndFilePos() >= $this->getRelativePosition();
    }
}
