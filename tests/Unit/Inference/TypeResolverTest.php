<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Inference;

use LanguageServer\Inference\TypeResolver;
use LanguageServer\ParsedDocument;
use LanguageServer\Test\Unit\ParserTestCase;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class TypeResolverTest extends ParserTestCase
{
    private const PARSER_FIXTURE = self::FIXTURE_DIRECTORY . '/TypeResolverFixture.php';

    private TypeResolver $subject;
    private ParsedDocument $document;

    protected function getSourceLocator(): SourceLocator
    {
        return new SingleFileSourceLocator(
            self::PARSER_FIXTURE,
            $this->getAstLocator()
        );
    }

    public function setUp(): void
    {
        $this->document = $this->parseFixture('TypeResolverFixture.php');
        $this->subject  = new TypeResolver($this->getClassReflector());

        /* print_r($this->document->getNodes()); die; */
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testGetType(NodeAbstract $node, ?string $expectedType): void
    {
        $actualType = $this->subject->getType($this->document, $node);

        $this->assertEquals($expectedType, $actualType);
    }

    /**
     * @return array<int, array<int, NodeAbstract|string|null>>
     */
    public function nodeProvider(): array
    {
        return [
            [
                new Variable('this'),
                'Fixtures\TypeResolverFixture',
            ],
            [
                new Variable('localVariable', [
                    'startFilePos' => 1,
                    'endFilePos' => 9999,
                ]),
                'Fixtures\LocalVariable',
            ],
            [
                new Variable('nonExistentVariable', [
                    'startFilePos' => 1,
                    'endFilePos' => 250,
                ]),
                null,
            ],
            [
                new Name('UnqualifiedClassName'),
                'Fixtures\UnqualifiedClassName',
            ],
            [
                new Name('\OtherFixtures\FullyQualifiedClassName'),
                '\OtherFixtures\FullyQualifiedClassName',
            ],
            [
                new Name('AliasedTypeResolverFixture'),
                'OtherFixtures\TypeResolverFixture',
            ],
            [
                new Param(new Variable('untypedParameter')),
                null,
            ],
            [
                new Param(new Variable('nativelyTypedParameter'), null, new Name('stdClass')),
                'stdClass',
            ],
            [
                new Identifier('TypeResolverFixture'),
                null,
            ],
            [
                new PropertyFetch(
                    new Variable('nativelyTypedParameter', [
                        'startFilePos' => 9990,
                        'endFilePos' => 9999,
                    ]),
                    new Identifier('publicProperty')
                ),
                'stdClass',
            ],
            [
                new PropertyFetch(
                    new PropertyFetch(
                        new Variable('this', [
                            'startFilePos' => 30,
                            'endFilePos' => 300,
                        ]),
                        new Identifier('nativelyTypedProperty')
                    ),
                    new Identifier('publicProperty')
                ),
                'stdClass',
            ],
            [
                new PropertyFetch(
                    new MethodCall(
                        new Variable('this'),
                        new Identifier('getTypeForPropertyFetchOnMethodCallReturnTypeFixture')
                    ),
                    new Identifier('publicProperty')
                ),
                'stdClass',
            ],
            [
                new MethodCall(
                    new MethodCall(
                        new Variable('this'),
                        new Identifier('getTypeForMethodCallOnMethodCallReturnTypeFixture')
                    ),
                    new Identifier('publicMethod')
                ),
                'Fixtures\TypeResolverFixture',
            ],
            [
                new MethodCall(
                    new MethodCall(
                        new Variable('this'),
                        new Identifier('getTypeForMethodCallWithNoReturnTypeFixture')
                    ),
                    new Identifier('publicMethod')
                ),
                null,
            ],
            [
                new MethodCall(
                    new MethodCall(
                        new Variable('this'),
                        new Identifier('getTypeForMethodCallReturningSelfFixture')
                    ),
                    new Identifier('publicMethod')
                ),
                'Fixtures\TypeResolverFixture',
            ],
            [
                new MethodCall(
                    new MethodCall(
                        new Variable('this'),
                        new Identifier('getTypeForMethodCallReturningParentFixture')
                    ),
                    new Identifier('publicMethod')
                ),
                'Fixtures\ParentFixture',
            ],
            [
                new PropertyFetch(
                    new Variable('nonExistentVariable', [
                        'startFilePos' => 1,
                        'endFilePos' => 900,
                    ]),
                    new Identifier('publicProperty')
                ),
                null,
            ],
            [
                new PropertyFetch(
                    new PropertyFetch(
                        new Variable('this'),
                        new Identifier('undefinedProperty'),
                    ),
                    new Identifier('publicInstanceVariable')
                ),
                null,
            ],
            [
                new PropertyFetch(
                    new MethodCall(
                        new Variable('this'),
                        new Identifier('getTypeForMethodCallWithDocBlockReturnTypeFixture'),
                    ),
                    new Identifier('publicInstanceVariable')
                ),
                'stdClass',
            ],
            [
                new PropertyFetch(
                    new PropertyFetch(
                        new Variable('this'),
                        new Identifier('docBlockTypedProperty'),
                    ),
                    new Identifier('publicInstanceVariable')
                ),
                'stdClass',
            ],
            [
                new PropertyFetch(
                    new Variable('instance', [
                        'startFilePos' => 99990,
                        'endFilePos' => 99999,
                    ]),
                    new Identifier('nativelyTypedProperty'),
                ),
                'Fixtures\TypeResolverFixture',
            ],
            /*
            [
                new PropertyFetch(
                    new Variable('paramWithDocBlockType'),
                    new Identifier('publicProperty'),
                ),
                'stdClass',
            ],
             */
            [
                new PropertyFetch(
                    new PropertyFetch(
                        new Variable('this', [
                            'startFilePos' => 30,
                            'endFilePos' => 300,
                        ]),
                        new Identifier('docblockProperty')
                    ),
                    new Identifier('publicProperty')
                ),
                'stdClass',
            ],
            [
                new PropertyFetch(
                    new Variable('variable', [
                        'startFilePos' => 1000,
                        'endFilePos' => 9999,
                    ]),
                    new Identifier('publicProperty')
                ),
                'stdClass',
            ],
            [
                new PropertyFetch(
                    new PropertyFetch(
                        new Variable('this', [
                            'startFilePos' => 1000,
                            'endFilePos' => 9999,
                        ]),
                        new Identifier('propertyAssignedInConstructor')
                    ),
                    new Identifier('publicProperty')
                ),
                'stdClass',
            ],
            [
                new PropertyFetch(
                    new PropertyFetch(
                        new Variable('this', [
                            'startFilePos' => 1000,
                            'endFilePos' => 9999,
                        ]),
                        new Identifier('propertyConstructedInConstructor')
                    ),
                    new Identifier('publicProperty')
                ),
                'stdClass',
            ],
            [
                new PropertyFetch(
                    new MethodCall(
                        new Variable('this'),
                        new Identifier('getTypeForScalarReturnValue')
                    ),
                    new Identifier('publicProperty')
                ),
                'string',
            ],
            [
                new Variable('foo', ['endFilePos' => 2650, 'startFilePos' => 2653]),
                'string',
            ],
        ];
    }
}
