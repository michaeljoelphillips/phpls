<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\CursorPosition;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class CursorPositionTest extends TestCase
{
    public function testGetters()
    {
        $subject = new CursorPosition(1, 10, 30);

        $this->assertEquals(1, $subject->getLine());
        $this->assertEquals(10, $subject->getCharacter());
        $this->assertEquals(30, $subject->getRelativePosition());
    }

    /**
     * @dataProvider coordinatesContainingNode
     */
    public function testContains(int $line, int $character, int $position, bool $contains)
    {
        $node = new PropertyFetch(new Variable('foo'), 'bar', [
            'startFilePos' => 10,
            'endFilePos' => 20,
        ]);

        $subject = new CursorPosition($line, $character, $position);

        $this->assertEquals($contains, $subject->contains($node));
    }

    public function coordinatesContainingNode(): array
    {
        return [
            [1, 10, 21, true],
            [1, 10, 9, true],
            [1, 10, 15, true],
            [1, 10, 22, false],
            [1, 10, 8, false],
        ];
    }

    /**
     * @dataProvider coordinatesWithinNode
     */
    public function testIsWithin(int $line, int $character, int $position, bool $isWithin)
    {
        $node = new PropertyFetch(new Variable('foo'), 'bar', [
            'startFilePos' => 10,
            'endFilePos' => 20,
        ]);

        $subject = new CursorPosition($line, $character, $position);

        $this->assertEquals($isWithin, $subject->isWithin($node));
    }

    public function coordinatesWithinNode(): array
    {
        return [
            [1, 10, 15, true],
            [1, 10, 21, false],
            [1, 10, 9, false],
        ];
    }

    public function testIsBordering()
    {
        $expr = $this->createMock(Expr::class);
        $node = new PropertyFetch($expr, 'testProperty', [
            'startFilePos' => 10,
            'endFilePos' => 20,
        ]);

        $subject = new CursorPosition(1, 10, 10);
        $this->assertTrue($subject->isBordering($node));

        $subject = new CursorPosition(1, 10, 20);
        $this->assertTrue($subject->isWithin($node));

        $subject = new CursorPosition(1, 10, 15);
        $this->assertFalse($subject->isBordering($node));
    }

    public function testIsSurrounding()
    {
        $expr = $this->createMock(Expr::class);
        $node = new PropertyFetch($expr, 'testProperty', [
            'startFilePos' => 10,
            'endFilePos' => 20,
        ]);

        $subject = new CursorPosition(1, 10, 15);
        $this->assertFalse($subject->isSurrounding($node));

        $subject = new CursorPosition(1, 10, 21);
        $this->assertTrue($subject->isSurrounding($node));

        $subject = new CursorPosition(1, 10, 9);
        $this->assertTrue($subject->isSurrounding($node));
    }
}
