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

class TypeResolverTest extends ParserTestCase
{
    private TypeResolver $subject;
    private Reflector $reflector;

    public function setUp() : void
    {
        $this->reflector = $this->createMock(Reflector::class);
        $this->subject   = new TypeResolver($this->reflector);
    }

    public function testGetTypeForThis() : void
    {
        $document = $this->parse('TypeResolverFixture.php');
        $node     = new Variable('this');

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Fixtures\Foo', $type);
    }

    public function testGetTypeForLocalVariable() : void
    {
        $document = $this->parse('TypeResolverFixture.php');
        $node     = new Variable('localVariable', [
            'startFilePos' => 1,
            'endFilePos' => 900,
        ]);

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Bar\Baz', $type);
    }

    public function testGetTypeForNonExistentLocalVariable() : void
    {
        $document = $this->parse('TypeResolverFixture.php');
        $node     = new Variable('nonExistentVariable', [
            'startFilePos' => 1,
            'endFilePos' => 250,
        ]);

        $type = $this->subject->getType($document, $node);

        $this->assertNull($type);
    }

    public function testGetTypeForUnqualifiedClassName() : void
    {
        $document = $this->parse('TypeResolverFixture.php');
        $node     = new Name('Baz');

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Bar\Baz', $type);
    }

    public function testGetTypeForQualifiedClassName() : void
    {
        $this->markTestIncomplete('Not currently implemented');

        $document = $this->parse('TypeResolverFixture.php');
        $node     = new Name('Bag\Bad');

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Bar\Bag\Bad', $type);
    }

    public function testGetTypeForFullyQualifiedClassName() : void
    {
        $document = $this->parse('TypeResolverFixture.php');
        $node     = new Name('\Bar\Baz');

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Bar\Baz', $type);
    }

    public function testGetTypeForClassAlias() : void
    {
        $document = $this->parse('TypeResolverFixture.php');
        $node     = new Name('FooBar');

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Foo\Bar', $type);
    }

    public function testGetTypeForUntypedParameter() : void
    {
        $document = $this->parse('TypeResolverFixture.php');
        $node     = new Param(new Variable('parameter'));

        $type = $this->subject->getType($document, $node);

        $this->assertNull($type);
    }

    public function testGetTypeForTypedParameter() : void
    {
        $document = $this->parse('TypeResolverFixture.php');
        $node     = new Param(new Variable('parameter'), null, new Name('Baz'));

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Bar\Baz', $type);
    }

    public function testGetTypeDefaultsToNull() : void
    {
        $document = $this->parse('TypeResolverFixture.php');
        $node     = new Identifier('Foo');

        $type = $this->subject->getType($document, $node);

        $this->assertNull($type);
    }

    public function testGetTypeForPropertyFetchOnVariable() : void
    {
        $document = $this->parse('TypeResolverFixture.php');
        $node     = new PropertyFetch(
            new Variable('parameter', [
                'startFilePos' => 30,
                'endFilePos' => 900,
            ]),
            new Identifier('foo')
        );

        $reflectedClass    = $this->createMock(ReflectionClass::class);
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

    public function testGetTypeForPropertyFetchOnNonExistentVariable() : void
    {
        $document = $this->parse('TypeResolverFixture.php');
        $node     = new PropertyFetch(
            new Variable('nonExistentVariable', [
                'startFilePos' => 30,
                'endFilePos' => 300,
            ]),
            new Identifier('foo')
        );

        $type = $this->subject->getType($document, $node);

        $this->assertNull($type);
    }

    public function testGetTypeForPropertyFetchOnUndefinedPropertyOnTheClass() : void
    {
        $document = $this->parse('TypeResolverFixture.php');
        $node     = new PropertyFetch(
            new Variable('parameter', [
                'startFilePos' => 30,
                'endFilePos' => 300,
            ]),
            new Identifier('foo')
        );

        $reflectedClass    = $this->createMock(ReflectionClass::class);
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

    public function testGetTypeForPropertyFetchOnPropertyAssignedInTheConstructor() : void
    {
        $document = $this->parse('TypeResolverFixture.php');

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

        $reflectedClass    = $this->createMock(ReflectionClass::class);
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

    public function testGetTypeForPropertyFetchOnTypedProperty() : void
    {
        $document = $this->parse('TypeResolverFixture.php');

        $node = new PropertyFetch(
            new PropertyFetch(
                new Variable('this', [
                    'startFilePos' => 30,
                    'endFilePos' => 300,
                ]),
                new Identifier('foobar')
            ),
            new Identifier('baz')
        );

        $type = $this->subject->getType($document, $node);

        $this->assertEquals('Foo\Bar', $type);
    }

    public function testGetTypeForPropertyFetchOnMethodCallReturnType() : void
    {
        $document = $this->parse('TypeResolverFixture.php');

        $node = new PropertyFetch(
            new MethodCall(
                new Variable('this'),
                new Identifier('methodCallTestMethod')
            ),
            new Identifier('bar')
        );

        $reflectedClass  = $this->createMock(ReflectionClass::class);
        $reflectedMethod = $this->createMock(ReflectionMethod::class);
        $reflectionType  = $this->createMock(ReflectionType::class);

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

    public function testGetTypeForMethodCallOnMethodCallReturnType() : void
    {
        $document = $this->parse('TypeResolverFixture.php');

        $node = new MethodCall(
            new MethodCall(
                new Variable('this'),
                new Identifier('anotherTestFunction')
            ),
            new Identifier('methodCallTestMethod')
        );

        $reflectedClass  = $this->createMock(ReflectionClass::class);
        $reflectedMethod = $this->createMock(ReflectionMethod::class);
        $reflectionType  = $this->createMock(ReflectionType::class);

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

    public function testGetTypeForMethodCallWithNoReturnType() : void
    {
        $document = $this->parse('TypeResolverFixture.php');

        $node = new MethodCall(
            new MethodCall(
                new Variable('this'),
                new Identifier('methodWithoutReturnType')
            ),
            new Identifier('methodCallTestMethod')
        );

        $reflectedClass  = $this->createMock(ReflectionClass::class);
        $reflectedMethod = $this->createMock(ReflectionMethod::class);
        $reflectionType  = $this->createMock(ReflectionType::class);

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
