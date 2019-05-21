<?php

declare(strict_types=1);

namespace LanguageServer\Test\Parser;

use LanguageServer\Test\ParserTestCase;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeAbstract;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ParsedDocumentTest extends ParserTestCase
{
    public function testGetClassName()
    {
        $subject = $this->parse('file:///tmp/Foo.php');

        $this->assertEquals('Fixtures\Foo', $subject->getClassName());
    }

    public function testGetMethod()
    {
        $subject = $this->parse('file:///tmp/Foo.php');

        $method = $subject->getMethod('anotherTestFunction');

        $this->assertEquals('anotherTestFunction', $method->name);
    }

    public function testFindNodes()
    {
        $subject = $this->parse('file:///tmp/Foo.php');

        $nodes = $subject->findNodes(ClassMethod::class);

        $this->assertEquals(2, count($nodes));
    }

    public function testSearchNodes()
    {
        $subject = $this->parse('file:///tmp/Foo.php');

        $nodes = $subject->searchNodes(
            function (NodeAbstract $node) {
                return $node instanceof ClassMethod;
            }
        );

        $this->assertEquals(2, count($nodes));
    }

    public function testGetUseStatements()
    {
        $subject = $this->parse('file:///tmp/Foo.php');

        $nodes = $subject->getUseStatements();

        $this->assertEquals(1, count($nodes));
        $this->assertEquals('Bar\Baz', $nodes[0]->uses[0]->name);
    }

    public function testGetNamespace()
    {
        $subject = $this->parse('file:///tmp/Foo.php');

        $this->assertEquals('Fixtures', $subject->getNamespace());
    }
}
