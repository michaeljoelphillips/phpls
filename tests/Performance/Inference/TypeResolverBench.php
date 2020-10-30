<?php

declare(strict_types=1);

namespace LanguageServer\Test\Performance\Inference;

use DI\ContainerBuilder;
use LanguageServer\Inference\TypeResolver;
use LanguageServer\ParsedDocument;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Parser;

use function assert;
use function file_get_contents;
use function is_string;

class TypeResolverBench
{
    private TypeResolver $subject;
    private Parser $parser;

    public function setUp(): void
    {
        $container = (new ContainerBuilder())
            ->addDefinitions(__DIR__ . '/../../../src/services.php')
            ->build();

        $container->set('project_root', '/home/nomad/Code/hermes');

        $this->parser  = $container->get(Parser::class);
        $this->subject = $container->get(TypeResolver::class);
    }

    /**
     * @BeforeMethods({"setUp"})
     */
    public function benchGetTypeOnPHPUnitMockObject(): void
    {
        $source = file_get_contents('/home/nomad/Code/hermes/tests/Unit/Twitch/SerializedTokenStorageTest.php');
        assert(is_string($source));

        $nodes    = $this->parser->parse($source);
        $document = new ParsedDocument('file:///tmp/file.php', $source, $nodes ?? []);

        $node = new MethodCall(
            new MethodCall(
                new Variable('this'),
                new Identifier('createMock')
            ),
            new Identifier('expects')
        );

        $this->subject->getType($document, $node);
    }
}
