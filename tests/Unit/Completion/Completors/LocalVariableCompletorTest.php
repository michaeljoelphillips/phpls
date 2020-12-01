<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Completion\Providers;

use LanguageServer\Completion\Completors\LocalVariableCompletor;
use LanguageServer\Inference\TypeResolver;
use LanguageServer\Test\Unit\ParserTestCase;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\MockObject\MockObject;

class LocalVariableCompletorTest extends ParserTestCase
{
    /** @var MockObject&TypeResolver */
    private $typeResolver;

    private LocalVariableCompletor $subject;

    public function setUp(): void
    {
        $this->typeResolver = $this->createMock(TypeResolver::class);
        $this->subject      = new LocalVariableCompletor($this->typeResolver);
    }

    public function testProviderSupportsVariableNodes(): void
    {
        $this->assertTrue($this->subject->supports(new Variable('foo')));
        $this->assertFalse($this->subject->supports(new PropertyFetch(new Variable('foo'), 'bar')));
    }

    public function testCompleteWithAssignedVariablesInAFunction(): void
    {
        $source = <<<PHP
            <?php
            function doSomething(Baz \$baz): void {
                \$foo = new Foo();
                \$bar = new Bar();
            }
        PHP;

        $document = $this->parse('file:///tmp/Foo.php', $source);
        $variable = new Variable('var', ['startFilePos' => 90, 'endLinePos' => 100]);

        $this
            ->typeResolver
            ->method('getType')
            ->will($this->onConsecutiveCalls('Baz', 'Foo', 'Bar'));

        $result = $this->subject->complete($variable, $document);

        self::assertIsArray($result);
        self::assertCount(3, $result);
        self::assertContainsOnlyInstancesOf(CompletionItem::class, $result);

        self::assertEquals('baz', $result[0]->label);
        self::assertEquals('Baz', $result[0]->detail);
        self::assertEquals(CompletionItemKind::VARIABLE, $result[0]->kind);

        self::assertEquals('foo', $result[1]->label);
        self::assertEquals('Foo', $result[1]->detail);
        self::assertEquals(CompletionItemKind::VARIABLE, $result[1]->kind);

        self::assertEquals('bar', $result[2]->label);
        self::assertEquals('Bar', $result[2]->detail);
        self::assertEquals(CompletionItemKind::VARIABLE, $result[2]->kind);
    }

    public function testCompleteWithVariablesInAnAnonymousFunction(): void
    {
        $source = <<<PHP
            <?php
            function doSomething(): void {
                \$foo = new Foo();
                \$baz = new Baz();

                return array_filter(
                    static function (Bar \$bar) use (\$baz): bool {
                        return \$bar->baz === false;
                    },
                    \$foo->getBar()
                );
            }
        PHP;

        $document = $this->parse('file:///tmp/Foo.php', $source);
        $variable = new Variable('var', ['startFilePos' => 175, 'endLinePos' => 180]);

        $this
            ->typeResolver
            ->method('getType')
            ->will($this->onConsecutiveCalls('Bar', 'Baz'));

        $result = $this->subject->complete($variable, $document);

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(CompletionItem::class, $result);

        self::assertEquals('bar', $result[0]->label);
        self::assertEquals('Bar', $result[0]->detail);
        self::assertEquals(CompletionItemKind::VARIABLE, $result[0]->kind);

        self::assertEquals('baz', $result[1]->label);
        self::assertEquals('Baz', $result[1]->detail);
        self::assertEquals(CompletionItemKind::VARIABLE, $result[1]->kind);
    }

    public function testCompleteReturnsAUniqueListOfVariables(): void
    {
        $source = <<<PHP
            <?php
            function doSomething(Baz \$baz): void {
                \$baz = new Foo();
                \$baz = new stdClass();
            }
        PHP;

        $document = $this->parse('file:///tmp/Foo.php', $source);
        $variable = new Variable('var', ['startFilePos' => 90, 'endLinePos' => 100]);

        $result = $this->subject->complete($variable, $document);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertContainsOnlyInstancesOf(CompletionItem::class, $result);
    }

    public function testCompleteAddsThisToCompletionListWhenClosureIsNotStatic(): void
    {
        $source = <<<PHP
            <?php
            class MyClass {
                public function doSomething(Baz \$baz): void {
                    return array_filter(
                        \$baz->getBars(),
                        function (Bar \$bar) {
                            return \$bar->foo === false;
                        }
                    );
                }
            }
        PHP;

        $document = $this->parse('file:///tmp/Foo.php', $source);
        $variable = new Variable('var', ['startFilePos' => 220, 'endLinePos' => 223]);

        $this
            ->typeResolver
            ->method('getType')
            ->will($this->onConsecutiveCalls('Bar', 'MyClass'));

        $result = $this->subject->complete($variable, $document);

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(CompletionItem::class, $result);

        self::assertEquals('this', $result[1]->label);
        self::assertEquals('MyClass', $result[1]->detail);
        self::assertEquals(CompletionItemKind::VARIABLE, $result[1]->kind);
    }
}
