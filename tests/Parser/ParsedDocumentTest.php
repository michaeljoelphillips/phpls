<?php

declare(strict_types=1);

namespace LanguageServer\Test\Parser;

use LanguageServer\CursorPosition;
use LanguageServer\Test\ParserTestCase;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeAbstract;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ParsedDocumentTest extends ParserTestCase
{
    public function testGetClassName()
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $this->assertEquals('Fixtures\ParsedDocumentFixture', $subject->getClassName());
    }

    public function testGetMethod()
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $method = $subject->getMethod('testMethod');

        $this->assertNotNull($method);
        $this->assertEquals('testMethod', $method->name);
    }

    public function testGetClassProperty()
    {
        $subject = $this->parse('TypedPropertyFixture.php');

        $property = $subject->getClassProperty('foo');

        $this->assertNotNull($property);
        $this->assertInstanceOf(Property::class, $property);
        $this->assertEquals('foo', $property->props[0]->name);
    }

    public function testGetClassPropertyWithNoProperty()
    {
        $subject = $this->parse('TypedPropertyFixture.php');

        $property = $subject->getClassProperty('baz');

        $this->assertNull($property);
    }

    public function testFindNodes()
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $nodes = $subject->findNodes(ClassMethod::class);

        $this->assertEquals(2, count($nodes));
    }

    public function testSearchNodes()
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $nodes = $subject->searchNodes(
            function (NodeAbstract $node) {
                return $node instanceof ClassMethod;
            }
        );

        $this->assertEquals(2, count($nodes));
    }

    public function testGetUseStatements()
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $nodes = $subject->getUseStatements();

        $this->assertEquals(1, count($nodes));
        $this->assertEquals('Psr\Log\LoggerInterface', $nodes[0]->uses[0]->name);
    }

    public function testGetNamespace()
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $this->assertEquals('Fixtures', $subject->getNamespace());
    }

    public function testGetSource()
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $this->assertEquals($this->loadFixture('ParsedDocumentFixture.php'), $subject->getSource());
    }

    public function testGetNodesAtCursor()
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $result = $subject->getNodesAtCursor(new CursorPosition(18, 36, 284));

        $this->assertContainsInstanceOf(MethodCall::class, $result);
    }

    public function testGetConstructorNodeWhenNoConstructorPresent()
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $constructor = $subject->getConstructorNode();

        $this->assertNull($constructor);
    }

    public function testGetConstructorNode()
    {
        $subject = $this->parse('NoConstructor.php');

        $constructor = $subject->getConstructorNode();

        $this->assertEquals('__construct', $constructor->name->name);
    }

    private function assertContainsInstanceOf(string $class, array $result)
    {
        foreach ($result as $object) {
            if ($object instanceof $class) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail('Failed asserting that array contains an instance of '.$class);
    }
}
