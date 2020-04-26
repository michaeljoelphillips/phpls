<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument\CompletionProvider;

use LanguageServer\Method\TextDocument\CompletionProvider\PropertyDocTagProvider;
use LanguageServerProtocol\CompletionItem;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;

class PropertyDocTagProviderTest extends TestCase
{
    public function testCompletionOnPropertiesFromClassDocblock() : void
    {
        $subject = new PropertyDocTagProvider();

        $expression = new PropertyFetch(new Variable('this'), 'bar');
        $reflection = $this->createMock(ReflectionClass::class);

        $reflection
            ->method('getDocComment')
            ->willReturn(<<<EOF
/**
 * @property string \$foo Some Property
 * @property int \$bar Bar
 * @property \Namespaced\Class_ \$baz
 * @property \stdClass invalidVar
 */
EOF
            );

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(4, $completionItems);
        $this->assertContainsOnly(CompletionItem::class, $completionItems);
        $this->assertEquals('foo', $completionItems[0]->label);
        $this->assertEquals('string', $completionItems[0]->detail);
        $this->assertEquals('Some Property', $completionItems[0]->documentation);
        $this->assertEquals('bar', $completionItems[1]->label);
        $this->assertEquals('int', $completionItems[1]->detail);
        $this->assertEquals('Bar', $completionItems[1]->documentation);
        $this->assertEquals('baz', $completionItems[2]->label);
        $this->assertEquals('\Namespaced\Class_', $completionItems[2]->detail);
    }
}
