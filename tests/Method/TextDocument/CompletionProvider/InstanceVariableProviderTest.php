<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument\CompletionProvider;

use LanguageServer\Method\TextDocument\CompletionProvider\InstanceVariableProvider;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class InstanceVariableProviderTest extends TestCase
{
    public function testSupports()
    {
        $subject = new InstanceVariableProvider();

        $this->assertTrue($subject->supports(new PropertyFetch(new Variable('Foo'), 'bar')));
        $this->assertFalse($subject->supports(new ClassConstFetch('Foo', 'bar')));
    }

    public function testComplete()
    {
        $subject = new InstanceVariableProvider();

        $expression = $this->createMock(Expr::class);
        $reflection = $this->createMock(ReflectionClass::class);
        $variable = $this->createMock(ReflectionProperty::class);

        $variable
            ->method('getName')
            ->willReturn('testProperty');

        $variable
            ->method('getDocblockTypeStrings')
            ->willReturn(['string', 'null']);

        $variable
            ->method('getDocComment')
            ->willReturn('testDocumentation');

        $reflection
            ->method('getProperties')
            ->willReturn([$variable]);

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertEquals(10, $completionItems[0]->kind);
        $this->assertEquals('testProperty', $completionItems[0]->label);
        $this->assertEquals('string|null', $completionItems[0]->detail);
        $this->assertEquals('testDocumentation', $completionItems[0]->documentation);
    }
}
