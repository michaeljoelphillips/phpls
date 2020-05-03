<?php

declare(strict_types=1);

namespace LanguageServer\Test\MessageHandler\TextDocument\CompletionProvider;

use LanguageServer\Completion\InstanceVariableProvider;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionType;

class InstanceVariableProviderTest extends TestCase
{
    public function testSupports() : void
    {
        $subject = new InstanceVariableProvider();

        $this->assertTrue($subject->supports(new PropertyFetch(new Variable('Foo'), 'bar')));
        $this->assertFalse($subject->supports(new ClassConstFetch('Foo', 'bar')));
    }

    public function testCompleteOnPropertiesWithDocblockTypes() : void
    {
        $subject = new InstanceVariableProvider();

        $expression = new PropertyFetch(new Variable('this'), 'bar');
        $reflection = $this->createMock(ReflectionClass::class);
        $property   = $this->createMock(ReflectionProperty::class);

        $property
            ->method('getName')
            ->willReturn('testProperty');

        $property
            ->method('getDocblockTypeStrings')
            ->willReturn(['string', 'null']);

        $property
            ->method('getDocComment')
            ->willReturn('testDocumentation');

        $reflection
            ->method('getProperties')
            ->willReturn([$property]);

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertEquals(10, $completionItems[0]->kind);
        $this->assertEquals('testProperty', $completionItems[0]->label);
        $this->assertEquals('string|null', $completionItems[0]->detail);
        $this->assertEquals('testDocumentation', $completionItems[0]->documentation);
    }

    public function testCompleteOnPropertiesWithNativeTypes() : void
    {
        $subject = new InstanceVariableProvider();

        $expression = new PropertyFetch(new Variable('this'), 'bar');
        $reflection = $this->createMock(ReflectionClass::class);
        $property   = $this->createMock(ReflectionProperty::class);
        $type       = $this->createMock(ReflectionType::class);

        $reflection
            ->method('getProperties')
            ->willReturn([$property]);

        $property
            ->method('getName')
            ->willReturn('testProperty');

        $property
            ->method('hasType')
            ->willReturn(true);

        $property
            ->method('getType')
            ->willReturn($type);

        $type
            ->method('__toString')
            ->willReturn('stdClass');

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertEquals(10, $completionItems[0]->kind);
        $this->assertEquals('testProperty', $completionItems[0]->label);
        $this->assertEquals('stdClass', $completionItems[0]->detail);
    }

    /**
     * @dataProvider variableProvider
     */
    public function testCompleteReturnsInstanceVariablesInScope(string $variable, bool $isPublic, bool $expected) : void
    {
        $subject = new InstanceVariableProvider();

        $expression = new PropertyFetch(new Variable($variable), 'bar');
        $reflection = $this->createMock(ReflectionClass::class);
        $variable   = $this->createMock(ReflectionProperty::class);

        $variable
            ->method('isPublic')
            ->willReturn($isPublic);

        $reflection
            ->method('getProperties')
            ->willReturn([$variable]);

        $this->assertEquals($expected, empty($subject->complete($expression, $reflection)) === false);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function variableProvider() : array
    {
        return [
            [
                'this',
                true,
                true,
            ],
            [
                'this',
                false,
                true,
            ],
            [
                'foo',
                true,
                true,
            ],
            [
                'foo',
                false,
                false,
            ],
        ];
    }
}
