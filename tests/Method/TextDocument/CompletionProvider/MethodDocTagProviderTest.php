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
 * @method string testMethod(int \$foo, string \$bar)
 */
EOF
            );

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertCount(1, $completionItems);
        $this->assertContainsOnly(CompletionItem::class, $completionItems);
        $this->assertEquals('testMethod', $completionItems[0]->label);
        $this->assertEquals(CompletionItemKind::METHOD, $completionItems[0]->kind);
        $this->assertEquals('public string testMethod(int $foo, string $bar)', $completionItems[0]->detail);
    }
}
