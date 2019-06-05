<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument\CompletionProvider;

use LanguageServer\Method\TextDocument\CompletionProvider\StaticMethodProvider;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionType;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class StaticMethodProviderTest extends TestCase
{
    public function testSupports()
    {
        $subject = new StaticMethodProvider();

        $this->assertTrue($subject->supports(new ClassConstFetch('Foo', 'bar')));
    }

    public function testComplete()
    {
        $subject = new StaticMethodProvider();

        $expression = $this->createMock(Expr::class);
        $reflection = $this->createMock(ReflectionClass::class);
        $method = $this->createMock(ReflectionMethod::class);
        $returnType = $this->createMock(ReflectionType::class);

        $returnType
            ->method('__toString')
            ->willReturn('string');

        $method
            ->method('getName')
            ->willReturn('testMethod');

        $method
            ->method('getReturnType')
            ->willReturn($returnType);

        $method
            ->method('getDocComment')
            ->willReturn('testDocumentation');

        $reflection
            ->method('getMethods')
            ->with(1)
            ->willReturn([$method]);

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertEquals(2, $completionItems[0]->kind);
        $this->assertEquals('string', $completionItems[0]->detail);
        $this->assertEquals('testMethod', $completionItems[0]->label);
        $this->assertEquals('testDocumentation', $completionItems[0]->documentation);
    }
}
