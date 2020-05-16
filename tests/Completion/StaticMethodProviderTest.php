<?php

declare(strict_types=1);

namespace LanguageServer\Test\Completion;

use LanguageServer\Completion\StaticMethodProvider;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use stdClass;

class StaticMethodProviderTest extends TestCase
{
    public function testSupports() : void
    {
        $subject = new StaticMethodProvider();

        $this->assertTrue($subject->supports(new ClassConstFetch(new Class_('Foo'), new Name('foo'))));
    }

    public function testCompleteOnlyReturnsStaticMethods() : void
    {
        $subject = new StaticMethodProvider();

        $expression = new ClassConstFetch(new Class_('Foo'), new Name('foo'));
        $reflection = $this->createMock(ReflectionClass::class);
        $method     = $this->createMock(ReflectionMethod::class);

        $method
            ->method('isStatic')
            ->willReturn(false);

        $reflection
            ->method('getMethods')
            ->willReturn([$method]);

        $this->assertEmpty($subject->complete($expression, $reflection));
    }

    /**
     * @dataProvider methodProvider
     */
    public function testCompleteReturnsMethodsInScope(string $class, stdClass $visibility, bool $expectation) : void
    {
        $subject = new StaticMethodProvider();

        $expression = new ClassConstFetch(new Class_($class), new Name('foo'));
        $reflection = $this->createMock(ReflectionClass::class);
        $method     = $this->createMock(ReflectionMethod::class);

        $method
            ->method('isStatic')
            ->willReturn(true);

        $method
            ->method('isPublic')
            ->willReturn($visibility->public);

        $method
            ->method('isProtected')
            ->willReturn($visibility->protected);

        $method
            ->method('isPrivate')
            ->willReturn($visibility->private);

        $method
            ->method('getDeclaringClass')
            ->willReturn($reflection);

        $reflection
            ->method('getMethods')
            ->willReturn([$method]);

        $completionItems = $subject->complete($expression, $reflection);

        $this->assertEquals($expectation, empty($completionItems) === false);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function methodProvider() : array
    {
        return [
            [
                'self',
                (object) [
                    'public' => true,
                    'protected' => false,
                    'private' => false,
                ],
                true,
            ],
            [
                'self',
                (object) [
                    'public' => false,
                    'protected' => false,
                    'private' => true,
                ],
                true,
            ],
            [
                'self',
                (object) [
                    'public' => false,
                    'protected' => true,
                    'private' => false,
                ],
                true,
            ],
            [
                'parent',
                (object) [
                    'public' => true,
                    'protected' => false,
                    'private' => false,
                ],
                true,
            ],
            [
                'parent',
                (object) [
                    'public' => false,
                    'protected' => true,
                    'private' => false,
                ],
                true,
            ],
            [
                'parent',
                (object) [
                    'public' => false,
                    'protected' => false,
                    'private' => true,
                ],
                false,
            ],
            [
                'Foo',
                (object) [
                    'public' => true,
                    'protected' => false,
                    'private' => false,
                ],
                true,
            ],
            [
                'Foo',
                (object) [
                    'public' => false,
                    'protected' => true,
                    'private' => false,
                ],
                false,
            ],
            [
                'Foo',
                (object) [
                    'public' => false,
                    'protected' => false,
                    'private' => true,
                ],
                false,
            ],
        ];
    }
}
