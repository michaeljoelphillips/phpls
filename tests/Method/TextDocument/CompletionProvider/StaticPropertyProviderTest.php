<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument\CompletionProvider;

use LanguageServer\Method\TextDocument\CompletionProvider\StaticPropertyProvider;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class StaticPropertyProviderTest extends TestCase
{
    public function testSupports()
    {
        $subject = new StaticPropertyProvider();

        $this->assertTrue($subject->supports(new ClassConstFetch('Foo', 'bar')));
    }

    public function testComplete()
    {
        $subject = new StaticPropertyProvider();

        $expression = $this->createMock(Expr::class);
        $reflection = $this->createMock(ReflectionClass::class);
        $property = $this->createMock(ReflectionProperty::class);

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
            ->with(1)
            ->willReturn([$property]);

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertEquals(10, $completionItems[0]->kind);
        $this->assertEquals('testProperty', $completionItems[0]->label);
        $this->assertEquals('string|null', $completionItems[0]->detail);
        $this->assertEquals('testDocumentation', $completionItems[0]->documentation);
    }
}
