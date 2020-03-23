<?php

declare(strict_types=1);

namespace LanguageServer\Tests\Method\TextDocument\CompletionProvider;

use LanguageServer\Method\TextDocument\CompletionProvider\MethodDocTagProvider;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;

class MethodDocTagProviderTest extends TestCase
{
    public function testCompletionWithClassMethodDocblock() : void
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
 */
EOF
            );

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(2, $completionItems);
        $this->assertContainsOnly(CompletionItem::class, $completionItems);
        $this->assertEquals('testMethod', $completionItems[0]->label);
        $this->assertEquals('bar', $completionItems[1]->label);
        $this->assertEquals(CompletionItemKind::METHOD, $completionItems[0]->kind);
        $this->assertEquals(CompletionItemKind::METHOD, $completionItems[1]->kind);
        $this->assertEquals('public testMethod(int $foo, string $bar): string', $completionItems[0]->detail);
        $this->assertEquals('public bar(int $foo, string $bar): stdClass', $completionItems[1]->detail);
    }
}
