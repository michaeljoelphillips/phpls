<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument\CompletionProvider;

use LanguageServer\Method\TextDocument\CompletionProvider\InstanceMethodProvider;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\TestCase;
use ReflectionMethod as CoreReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionType;

class InstanceMethodProviderTest extends TestCase
{
    public function testSupports() : void
    {
        $subject = new InstanceMethodProvider();

        $this->assertTrue($subject->supports(new PropertyFetch(new Variable('Foo'), 'bar')));
    }

    public function testCompleteWithReturnTypeDeclarations() : void
    {
        $subject = new InstanceMethodProvider();

        $expression = $this->createMock(Expr::class);
        $reflection = $this->createMock(ReflectionClass::class);
        $method     = $this->createMock(ReflectionMethod::class);
        $type       = $this->createMock(ReflectionType::class);

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

        $method
            ->method('getModifiers')
            ->willReturn(CoreReflectionMethod::IS_FINAL + CoreReflectionMethod::IS_PUBLIC);

        $reflection
            ->method('getMethods')
            ->willReturn([$method]);

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertEquals(2, $completionItems[0]->kind);
        $this->assertEquals('testMethod', $completionItems[0]->label);
        $this->assertEquals('testDocumentation', $completionItems[0]->documentation);
        $this->assertEquals('final public testMethod(): mixed', $completionItems[0]->detail);
    }

    public function testCompleteWithDocBlockReturnTypes() : void
    {
        $subject = new InstanceMethodProvider();

        $expression = $this->createMock(Expr::class);
        $reflection = $this->createMock(ReflectionClass::class);
        $method     = $this->createMock(ReflectionMethod::class);

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

        $method
            ->method('getModifiers')
            ->willReturn(CoreReflectionMethod::IS_PUBLIC);

        $reflection
            ->method('getMethods')
            ->willReturn([$method]);

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertEquals(2, $completionItems[0]->kind);
        $this->assertEquals('testMethod', $completionItems[0]->label);
        $this->assertEquals('testDocumentation', $completionItems[0]->documentation);
        $this->assertEquals('public testMethod(): int|float', $completionItems[0]->detail);
    }

    public function testCompleteOnNullableType() : void
    {
        $subject = new InstanceMethodProvider();

        $expression = $this->createMock(Expr::class);
        $reflection = $this->createMock(ReflectionClass::class);
        $method     = $this->createMock(ReflectionMethod::class);

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

        $method
            ->method('getModifiers')
            ->willReturn(CoreReflectionMethod::IS_PUBLIC);

        $reflection
            ->method('getMethods')
            ->willReturn([$method]);

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertEquals(2, $completionItems[0]->kind);
        $this->assertEquals('testMethod', $completionItems[0]->label);
        $this->assertEquals('testDocumentation', $completionItems[0]->documentation);
        $this->assertEquals('public testMethod(): int|float', $completionItems[0]->detail);
    }

    public function testCompleteReturnsOnlyNonStaticMethods() : void
    {
        $subject = new InstanceMethodProvider();

        $expression = $this->createMock(Expr::class);
        $reflection = $this->createMock(ReflectionClass::class);
        $method     = $this->createMock(ReflectionMethod::class);

        $method
            ->method('isStatic')
            ->willReturn(true);

        $reflection
            ->method('getMethods')
            ->willReturn([$method]);

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertEmpty($completionItems);
    }
}
