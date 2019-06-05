<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument\CompletionProvider;

use LanguageServer\Method\TextDocument\CompletionProvider\ClassConstantProvider;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ClassConstantProviderTest extends TestCase
{
    public function testSupports()
    {
        $subject = new ClassConstantProvider();

        $this->assertTrue($subject->supports(new ClassConstFetch('Foo', 'bar')));
    }

    public function testComplete()
    {
        $subject = new ClassConstantProvider();

        $expression = $this->createMock(Expr::class);
        $reflection = $this->createMock(ReflectionClass::class);
        $constant = $this->createMock(ReflectionClassConstant::class);

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
