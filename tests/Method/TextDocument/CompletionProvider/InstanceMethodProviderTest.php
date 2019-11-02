<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument\CompletionProvider;

use LanguageServer\Method\TextDocument\CompletionProvider\InstanceMethodProvider;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionType;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class InstanceMethodProviderTest extends TestCase
{
    public function testSupports()
    {
        $subject = new InstanceMethodProvider();

        $this->assertTrue($subject->supports(new PropertyFetch(new Variable('Foo'), 'bar')));
        $this->assertFalse($subject->supports(new ClassConstFetch('Foo', 'bar')));
    }

    public function testCompleteWithReturnTypeDeclarations()
    {
        $subject = new InstanceMethodProvider();

        $expression = $this->createMock(Expr::class);
        $reflection = $this->createMock(ReflectionClass::class);
        $method = $this->createMock(ReflectionMethod::class);
        $type = $this->createMock(ReflectionType::class);

        $type
            ->method('__toString')
            ->willReturn('string');

        $method
            ->method('getName')
            ->willReturn('testMethod');

        $method
            ->method('getReturnType')
            ->willReturn($type);

        $method
            ->method('getDocComment')
            ->willReturn('testDocumentation');

        $reflection
            ->method('getMethods')
            ->willReturn([$method]);

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertEquals(2, $completionItems[0]->kind);
        $this->assertEquals('string', $completionItems[0]->detail);
        $this->assertEquals('testMethod', $completionItems[0]->label);
        $this->assertEquals('testDocumentation', $completionItems[0]->documentation);
    }

    public function testCompleteWithDocBlockReturnTypes()
    {
        $subject = new InstanceMethodProvider();

        $expression = $this->createMock(Expr::class);
        $reflection = $this->createMock(ReflectionClass::class);
        $method = $this->createMock(ReflectionMethod::class);

        $method
            ->method('getName')
            ->willReturn('testMethod');

        $method
            ->method('getReturnType')
            ->willReturn(null);

        $method
            ->method('getDocBlockReturnTypes')
            ->willReturn(['int', 'float']);

        $method
            ->method('getDocComment')
            ->willReturn('testDocumentation');

        $reflection
            ->method('getMethods')
            ->willReturn([$method]);

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertEquals(2, $completionItems[0]->kind);
        $this->assertEquals('int|float', $completionItems[0]->detail);
        $this->assertEquals('testMethod', $completionItems[0]->label);
        $this->assertEquals('testDocumentation', $completionItems[0]->documentation);
    }
}
