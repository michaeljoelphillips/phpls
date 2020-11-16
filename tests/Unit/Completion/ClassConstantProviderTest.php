<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Completion;

use LanguageServer\Completion\ClassConstantProvider;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

class ClassConstantProviderTest extends TestCase
{
    public function testSupports(): void
    {
        $subject = new ClassConstantProvider();

        $this->assertTrue($subject->supports(new ClassConstFetch(new Name('Foo'), 'bar')));
    }

    public function testComplete(): void
    {
        $subject = new ClassConstantProvider();

        $expression = $this->createMock(Expr::class);
        $reflection = $this->createMock(ReflectionClass::class);
        $constant   = $this->createMock(ReflectionClassConstant::class);

        $constant
            ->method('getName')
            ->willReturn('TEST_CONSTANT');

        $reflection
            ->method('getReflectionConstants')
            ->willReturn([$constant]);

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertEquals('TEST_CONSTANT', $completionItems[0]->label);
    }
}
