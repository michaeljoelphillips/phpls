<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\TypeResolver;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TypeResolverTest extends ParserTestCase
{
    private $subject;
    private $reflector;

    public function setUp(): void
    {
        $this->reflector = $this->createMock(Reflector::class);
        $this->subject = new TypeResolver($this->reflector);
    }

    public function testGetTypeForThis()
    {
        $document = $this->parse('Foo.php');
        $node = new Variable('this');

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Fixtures\Foo', $type);
    }

    public function testGetTypeForLocalVariable()
    {
        $document = $this->parse('Foo.php');
        $node = new Variable('localVariable', [
            'startFilePos' => 1,
            'endFilePos' => 900,
        ]);

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Bar\Baz', $type);
    }

    public function testGetTypeForNonExistentLocalVariable()
    {
        $document = $this->parse('Foo.php');
        $node = new Variable('nonExistentVariable', [
            'startFilePos' => 1,
            'endFilePos' => 250,
        ]);

        $type = $this->subject->getType($document, $node);

        $this->assertNull($type);
    }

    public function testGetTypeForClassName()
    {
        $document = $this->parse('Foo.php');
        $node = new Name('Baz');

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Bar\Baz', $type);
    }

    public function testGetTypeForUntypedParameter()
    {
        $document = $this->parse('Foo.php');
        $node = new Param(new Variable('parameter'));

        $type = $this->subject->getType($document, $node);

        $this->assertNull($type);
    }

    public function testGetTypeForTypedParameter()
    {
        $document = $this->parse('Foo.php');
        $node = new Param(new Variable('parameter'), null, new Name('Baz'));

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Bar\Baz', $type);
    }

    public function testGetTypeDefaultsToNull()
    {
        $document = $this->parse('Foo.php');
        $node = new Identifier('Foo');

        $type = $this->subject->getType($document, $node);

        $this->assertNull($type);
    }

    public function testGetTypeForPropertyFetchOnVariable()
    {
        $document = $this->parse('Foo.php');
        $node = new PropertyFetch(
            new Variable('parameter', [
                'startFilePos' => 30,
                'endFilePos' => 900,
            ]),
            new Identifier('foo')
        );

        $reflectedClass = $this->createMock(ReflectionClass::class);
        $reflectedProperty = $this->createMock(ReflectionProperty::class);

        $this
            ->reflector
            ->method('reflect')
            ->willReturn($reflectedClass);

        $reflectedClass
            ->method('getProperty')
            ->willReturn($reflectedProperty);

        $reflectedProperty
            ->method('getDocBlockTypeStrings')
            ->willReturn(['Baz']);

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Bar\Baz', $type);
    }

    public function testGetTypeForPropertyFetchOnNonExistentVariable()
    {
        $document = $this->parse('Foo.php');
        $node = new PropertyFetch(
            new Variable('nonExistentVariable', [
                'startFilePos' => 30,
                'endFilePos' => 300,
            ]),
            new Identifier('foo')
        );

        $type = $this->subject->getType($document, $node);

        $this->assertNull($type);
    }

    public function testGetTypeForPropertyFetchOnUndefinedPropertyOnTheClass()
    {
        $document = $this->parse('Foo.php');
        $node = new PropertyFetch(
            new Variable('parameter', [
                'startFilePos' => 30,
                'endFilePos' => 300,
            ]),
            new Identifier('foo')
        );

        $reflectedClass = $this->createMock(ReflectionClass::class);
        $reflectedProperty = $this->createMock(ReflectionProperty::class);

        $this
            ->reflector
            ->method('reflect')
            ->willReturn($reflectedClass);

        $reflectedClass
            ->method('getProperty')
            ->willReturn(null);

        $type = $this->subject->getType($document, $node);

        $this->assertNull($type);
    }

    public function testGetTypeForPropertyFetchOnPropertyAssignedInTheConstructor()
    {
        $document = $this->parse('Foo.php');

        $node = new PropertyFetch(
            new PropertyFetch(
                new Variable('this', [
                    'startFilePos' => 30,
                    'endFilePos' => 300,
                ]),
                new Identifier('bar')
            ),
            new Identifier('baz')
        );

        $reflectedClass = $this->createMock(ReflectionClass::class);
        $reflectedProperty = $this->createMock(ReflectionProperty::class);

        $this
            ->reflector
            ->method('reflect')
            ->willReturn($reflectedClass);

        $reflectedClass
            ->method('getProperty')
            ->willReturn($reflectedProperty);

        $reflectedProperty
            ->method('getDocBlockTypeStrings')
            ->willReturn([]);

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Bar\Bar', $type);
    }

    public function testGetTypeForPropertyFetchOnMethodCallReturnType()
    {
        $document = $this->parse('Foo.php');

        $node = new PropertyFetch(
            new MethodCall(
                new Variable('this'),
                new Identifier('methodCallTestMethod')
            ),
            new Identifier('bar')
        );

        $reflectedClass = $this->createMock(ReflectionClass::class);
        $reflectedMethod = $this->createMock(ReflectionMethod::class);
        $reflectionType = $this->createMock(ReflectionType::class);

        $reflectionType
            ->method('__toString')
            ->willReturn('Fixtures\Foo');

        $this
            ->reflector
            ->method('reflect')
            ->willReturn($reflectedClass);

        $reflectedClass
            ->method('getMethod')
            ->willReturn($reflectedMethod);

        $reflectedMethod
            ->method('getReturnType')
            ->willReturn($reflectionType);

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Fixtures\Foo', $type);
    }

    public function testGetTypeForMethodCallOnMethodCallReturnType()
    {
        $document = $this->parse('Foo.php');

        $node = new MethodCall(
            new MethodCall(
                new Variable('this'),
                new Identifier('anotherTestFunction')
            ),
            new Identifier('methodCallTestMethod')
        );

        $reflectedClass = $this->createMock(ReflectionClass::class);
        $reflectedMethod = $this->createMock(ReflectionMethod::class);
        $reflectionType = $this->createMock(ReflectionType::class);

        $this
            ->reflector
            ->method('reflect')
            ->willReturn($reflectedClass);

        $reflectedClass
            ->method('getMethod')
            ->willReturn($reflectedMethod);

        $reflectedMethod
            ->method('getReturnType')
            ->willReturn($reflectionType);

        $reflectionType
            ->method('__toString')
            ->willReturn('Bar\Bar');

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Bar\Bar', $type);
    }

    public function testGetTypeForMethodCallWithNoReturnType()
    {
        $document = $this->parse('Foo.php');

        $node = new MethodCall(
            new MethodCall(
                new Variable('this'),
                new Identifier('methodWithoutReturnType')
            ),
            new Identifier('methodCallTestMethod')
        );

        $reflectedClass = $this->createMock(ReflectionClass::class);
        $reflectedMethod = $this->createMock(ReflectionMethod::class);
        $reflectionType = $this->createMock(ReflectionType::class);

        $this
            ->reflector
            ->method('reflect')
            ->willReturn($reflectedClass);

        $reflectedClass
            ->method('getMethod')
            ->willReturn($reflectedMethod);

        $reflectedMethod
            ->method('getReturnType')
            ->willReturn($reflectionType);

        $reflectionType
            ->method('__toString')
            ->willReturn('');

        $type = $this->subject->getType($document, $node);

        $this->assertNull($type);
    }
}