<?php

declare(strict_types=1);

namespace LanguageServer\Test\Parser;

use LanguageServer\CursorPosition;
use LanguageServer\Test\ParserTestCase;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeAbstract;
use function count;

class ParsedDocumentTest extends ParserTestCase
{
    public function testGetClassName() : void
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $this->assertEquals('Fixtures\ParsedDocumentFixture', $subject->getClassName());
    }

    public function testGetMethod() : void
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $method = $subject->getMethod('testMethod');

        $this->assertNotNull($method);
        $this->assertEquals('testMethod', $method->name);
    }

    public function testGetClassProperty() : void
    {
        $subject = $this->parse('TypedPropertyFixture.php');

        $property = $subject->getClassProperty('foo');

        $this->assertNotNull($property);
        $this->assertInstanceOf(Property::class, $property);
        $this->assertEquals('foo', $property->props[0]->name);
    }

    public function testGetClassPropertyWithNoProperty() : void
    {
        $subject = $this->parse('TypedPropertyFixture.php');

        $property = $subject->getClassProperty('baz');

        $this->assertNull($property);
    }

    public function testFindNodes() : void
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $nodes = $subject->findNodes(ClassMethod::class);

        $this->assertEquals(3, count($nodes));
    }

    public function testSearchNodes() : void
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $nodes = $subject->searchNodes(
            static function (NodeAbstract $node) {
                return $node instanceof ClassMethod;
            }
        );

        $this->assertEquals(3, count($nodes));
    }

    public function testGetUseStatements() : void
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $nodes = $subject->getUseStatements();

        $this->assertEquals(1, count($nodes));
        $this->assertEquals('Psr\Log\LoggerInterface', $nodes[0]->uses[0]->name);
    }

    public function testGetNamespace() : void
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $this->assertEquals('Fixtures', $subject->getNamespace());
    }

    public function testGetSource() : void
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $this->assertEquals($this->loadFixture('ParsedDocumentFixture.php'), $subject->getSource());
    }

    public function testGetNodesAtCursor() : void
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $result = $subject->getNodesAtCursor(new CursorPosition(18, 36, 284));

        $this->assertContainsInstanceOf(MethodCall::class, $result);
    }

    public function testGetInnermostNodeAtCursor() : void
    {
        $subject = $this->parse('ParsedDocumentFixture.php');
        $result  = $subject->getInnermostNodeAtCursor(new CursorPosition(25, 19, 379));

        $this->assertInstanceOf(Name::class, $result);
        $this->assertEquals('Foo', (string) $result);
    }

    public function testGetInntermostNodesAtCursorWhenCursorIsNotWithinAnyNodes() : void
    {
        $subject = $this->parse('ParsedDocumentFixture.php');
        $result  = $subject->getInnermostNodeAtCursor(new CursorPosition(25, 900, 999));

        $this->assertNull($result);
    }

    public function testGetConstructorNodeWhenNoConstructorPresent() : void
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        $constructor = $subject->getConstructorNode();

        $this->assertNull($constructor);
    }

    public function testGetConstructorNode() : void
    {
        $subject = $this->parse('NoConstructor.php');

        $constructor = $subject->getConstructorNode();

        $this->assertEquals('__construct', $constructor->name->name);
    }

    /**
     * @dataProvider cursorPositionProvider
     */
    public function testGetCursorPosition(int $line, int $character, int $expected) : void
    {
        $subject = $this->parse('ParsedDocumentFixture.php');

        self::assertEquals($expected, $subject->getCursorPosition($line, $character)->getRelativePosition());
    }

    /**
     * @return array<int, array<int, int>>
     */
    public function cursorPositionProvider() : array
    {
        return [
            [0, 0, 0],
            [0, 1, 1],
            [0, 2, 2],
            [0, 3, 3],
            [0, 4, 4],
            [2, 0, 7],
        ];
    }

    /**
     * @param object[] $result
     */
    private function assertContainsInstanceOf(string $class, array $result) : void
    {
        foreach ($result as $object) {
            if ($object instanceof $class) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail('Failed asserting that array contains an instance of ' . $class);
    }
}
