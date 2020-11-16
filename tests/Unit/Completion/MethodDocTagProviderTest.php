<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Completion;

use LanguageServer\Completion\MethodDocTagProvider;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;

class MethodDocTagProviderTest extends TestCase
{
    public function testCompletionOnInstanceMethodsFromClassMethodDocblock(): void
    {
        $subject = new MethodDocTagProvider();

        $expression = new PropertyFetch(new Variable('foo'), 'bar');
        $reflection = $this->createMock(ReflectionClass::class);

        $reflection
            ->method('getDocComment')
            ->willReturn(<<<EOF
/**
 * @method foo
 * @method string testMethod(int \$foo, string \$bar)
 * @method stdClass bar(int \$foo, string \$bar)
 * @method static stdClass bar(int \$foo, string \$bar)
 * @method \Namespaced\Class_ transcriptions(string \$sid)
 */
EOF
            );

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(3, $completionItems);
        $this->assertContainsOnly(CompletionItem::class, $completionItems);
        $this->assertEquals('testMethod', $completionItems[0]->label);
        $this->assertEquals('bar', $completionItems[1]->label);
        $this->assertEquals(CompletionItemKind::METHOD, $completionItems[0]->kind);
        $this->assertEquals(CompletionItemKind::METHOD, $completionItems[1]->kind);
        $this->assertEquals('public testMethod(int $foo, string $bar): string', $completionItems[0]->detail);
        $this->assertEquals('public bar(int $foo, string $bar): stdClass', $completionItems[1]->detail);
        $this->assertEquals('public transcriptions(string $sid): \Namespaced\Class_', $completionItems[2]->detail);
    }

    public function testCompletionOnStaticMethodsFromClassMethodDocblock(): void
    {
        $subject = new MethodDocTagProvider();

        $expression = new ClassConstFetch(new Name('Foo'), new Identifier('foo'));
        $reflection = $this->createMock(ReflectionClass::class);

        $reflection
            ->method('getDocComment')
            ->willReturn(<<<EOF
/**
 * @method foo
 * @method string testMethod(int \$foo, string \$bar)
 * @method bar(int \$foo, string \$bar)
 * @method static stdClass baz(int \$foo, string \$bar)
 */
EOF
            );

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertContainsOnly(CompletionItem::class, $completionItems);
        $this->assertEquals('baz', $completionItems[0]->label);
        $this->assertEquals(CompletionItemKind::METHOD, $completionItems[0]->kind);
        $this->assertEquals('public static baz(int $foo, string $bar): stdClass', $completionItems[0]->detail);
    }
}
