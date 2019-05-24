<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\CursorPosition;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
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

    public function testContains()
    {
        $expr = $this->createMock(Expr::class);
        $node = new PropertyFetch($expr, 'testProperty', [
            'startFilePos' => 10,
            'endFilePos' => 20,
        ]);

        $subject = new CursorPosition(1, 10, 21);
        $this->assertTrue($subject->contains($node));

        $subject = new CursorPosition(1, 10, 9);
        $this->assertTrue($subject->contains($node));

        $subject = new CursorPosition(1, 10, 15);
        $this->assertTrue($subject->contains($node));

        $subject = new CursorPosition(1, 10, 22);
        $this->assertFalse($subject->contains($node));

        $subject = new CursorPosition(1, 10, 8);
        $this->assertFalse($subject->contains($node));
    }

    public function testIsWithin()
    {
        $expr = $this->createMock(Expr::class);
        $node = new PropertyFetch($expr, 'testProperty', [
            'startFilePos' => 10,
            'endFilePos' => 20,
        ]);

        $subject = new CursorPosition(1, 10, 15);
        $this->assertTrue($subject->isWithin($node));

        $subject = new CursorPosition(1, 10, 21);
        $this->assertFalse($subject->isWithin($node));

        $subject = new CursorPosition(1, 10, 9);
        $this->assertFalse($subject->isWithin($node));
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
