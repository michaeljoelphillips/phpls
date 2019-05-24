<?php

declare(strict_types=1);

namespace LanguageServer\Test\Parser;

use LanguageServer\CursorPosition;
use LanguageServer\Test\ParserTestCase;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeAbstract;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ParsedDocumentTest extends ParserTestCase
{
    public function testGetClassName()
    {
        $subject = $this->parse('Foo.php');

        $this->assertEquals('Fixtures\Foo', $subject->getClassName());
    }

    public function testGetMethod()
    {
        $subject = $this->parse('Foo.php');

        $method = $subject->getMethod('anotherTestFunction');

        $this->assertEquals('anotherTestFunction', $method->name);
    }

    public function testFindNodes()
    {
        $subject = $this->parse('Foo.php');

        $nodes = $subject->findNodes(ClassMethod::class);

        $this->assertEquals(2, count($nodes));
    }

    public function testSearchNodes()
    {
        $subject = $this->parse('Foo.php');

        $nodes = $subject->searchNodes(
            function (NodeAbstract $node) {
                return $node instanceof ClassMethod;
            }
        );

        $this->assertEquals(2, count($nodes));
    }

    public function testGetUseStatements()
    {
        $subject = $this->parse('Foo.php');

        $nodes = $subject->getUseStatements();

        $this->assertEquals(1, count($nodes));
        $this->assertEquals('Bar\Baz', $nodes[0]->uses[0]->name);
    }

    public function testGetNamespace()
    {
        $subject = $this->parse('Foo.php');

        $this->assertEquals('Fixtures', $subject->getNamespace());
    }

    public function testGetSource()
    {
        $subject = $this->parse('Foo.php');

        $this->assertEquals($this->loadFixture('Foo.php'), $subject->getSource());
    }

    public function testGetNodesAtCursor()
    {
        $subject = $this->parse('Foo.php');

        $result = $subject->getNodesAtCursor(new CursorPosition(16, 36, 189));

        $this->assertContainsInstanceOf(MethodCall::class, $result);
    }

    public function testGetNodesBesideCursor()
    {
        $subject = $this->parse('Foo.php');

        $result = $subject->getNodesBesideCursor(new CursorPosition(16, 36, 211));
        $this->assertContainsOnlyInstancesOf(Return_::class, $result);

        $result = $subject->getNodesBesideCursor(new CursorPosition(16, 36, 210));
        $this->assertContainsOnlyInstancesOf(MethodCall::class, $result);
    }

    public function testGetConstructorNode()
    {
        $subject = $this->parse('Foo.php');
        $constructor = $subject->getConstructorNode();
        $this->assertNull($constructor);

        $subject = $this->parse('Bar.php');
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
