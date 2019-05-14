<?php

declare(strict_types=1);

namespace LanguageServer\LSP;

use PhpParser\NodeAbstract;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class CursorPosition
{
    /** @var int */
    private $line;

    /** @var int */
    private $character;

    /** @var int */
    private $relativePosition;

    /**
     * @param int $line
     * @param int $character
     * @param int $relativePosition
     */
    public function __construct(int $line, int $character, int $relativePosition)
    {
        $this->line = $line;
        $this->character = $character;
        $this->relativePosition = $relativePosition;
    }

    /**
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @return int
     */
    public function getCharacter(): int
    {
        return $this->character;
    }

    /**
     * @return int
     */
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
        return $node->getStartFilePos() - 1 == $this->getRelativePosition()
            || $node->getEndFilePos() + 1 == $this->getRelativePosition();
    }

    public function isBordering(NodeAbstract $node): bool
    {
        return $node->getStartFilePos() == $this->getRelativePosition()
            || $node->getEndFilePos() == $this->getRelativePosition();
    }

    public function isWithin(NodeAbstract $node): bool
    {
        return $node->getStartFilePos() <= $this->getRelativePosition()
            && $node->getEndFilePos() >= $this->getRelativePosition();
    }
}
