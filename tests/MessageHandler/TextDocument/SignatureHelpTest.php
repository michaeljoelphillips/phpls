<?php

declare(strict_types=1);

namespace LanguageServer\Test\MessageHandler\TextDocument;

use LanguageServer\Inference\TypeResolver;
use LanguageServer\MessageHandler\TextDocument\SignatureHelp;
use LanguageServer\ParsedDocument;
use LanguageServer\Reflection\RegistrySourceLocator;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Test\ParserTestCase;
use LanguageServer\TextDocumentRegistry;
use PhpParser\Parser;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class SignatureHelpTest extends ParserTestCase
{
    private Parser $parser;
    private TextDocumentRegistry $registry;
    private SignatureHelp $subject;

    public function setUp() : void
    {
        $this->parser   = $this->getParser();
        $this->registry = $this->setUpRegistry();

        $classReflector = $this->getClassReflector();
        $typeResolver   = new TypeResolver($classReflector);
        $this->subject  = new SignatureHelp($classReflector, $this->getFunctionReflector(), $typeResolver, $this->registry);
    }

    protected function getSourceLocator() : SourceLocator
    {
        return new RegistrySourceLocator(
            new AstLocator(
                $this->parser,
                function () {
                    return $this->getFunctionReflector();
                }
            ),
            $this->registry
        );
    }

    private function setUpRegistry() : TextDocumentRegistry
    {
        $registry = new TextDocumentRegistry();

        $registry->add(
            new ParsedDocument(
                'file:///tmp/foo.php',
                $source = $this->loadFixture('SignatureHelpFixture.php'),
                $this->parser->parse($source)
            )
        );

        $registry->add(
            new ParsedDocument(
                'file:///tmp/bar.php',
                $source = $this->loadFixture('NamespacedFunctionsFixture.php'),
                $this->parser->parse($source)
            )
        );

        return $registry;
    }

    /**
     * @dataProvider cursorPositionsProvider
     */
    public function testSignatureHelp(int $line, int $character, int $activeParameter, string $label) : void
    {
        $request = new RequestMessage(1, 'textDocument/signatureHelp', [
            'textDocument' => ['uri' => 'file:///tmp/foo.php'],
            'position' => [
                'line' => $line,
                'character' => $character,
            ],
        ]);

        $next = function () : void {
            $this->fail('The next method should never be called');
        };

        $response = $this->subject->__invoke($request, $next);

        $this->assertCount(1, $response->result->signatures);
        $this->assertEquals($activeParameter, $response->result->activeParameter);
        $this->assertEquals($label, $response->result->signatures[0]->label);
    }

    public function testSignatureHelpReturnsEmptyResponseWhenNoExpressionFound() : void
    {
        $request = new RequestMessage(1, 'textDocument/signatureHelp', [
            'textDocument' => ['uri' => 'file:///tmp/foo.php'],
            'position' => [
                'line' => 31,
                'character' => 9,
            ],
        ]);

        $next = function () : void {
            $this->fail('The next method should never be called');
        };

        $response = $this->subject->__invoke($request, $next);

        $this->assertEmpty($response->result->activeParameter);
    }

    public function testSignatureHelpReturnsEmptyResponseWhenNoConstructorFound() : void
    {
        $request = new RequestMessage(1, 'textDocument/signatureHelp', [
            'textDocument' => ['uri' => 'file:///tmp/foo.php'],
            'position' => [
                'line' => 30,
                'character' => 33,
            ],
        ]);

        $next = function () : void {
            $this->fail('The next method should never be called');
        };

        $response = $this->subject->__invoke($request, $next);

        $this->assertEmpty($response->result->activeParameter);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function cursorPositionsProvider() : array
    {
        return [
            [18, 19, 0, 'stdClass $bar, array $baz'],
            [19, 36, 1, 'stdClass $bar, array $baz'],
            [20, 33, 0, 'stdClass $bar, array $baz'],
            [21, 16, 0, 'stdClass $bar, array $baz'],
            [23, 13, 1, 'stdClass $bar, array $baz'],
            [27, 34, 0, 'stdClass $bar, array $baz'],
            [28, 13, 1, 'stdClass $bar, array $baz'],
            [27, 25, 0, '$baz'],
            [27, 53, 1, '$baz'],
            [38, 26, 0, 'int $code, string $body'],
            /* [43, 20, 0, 'string $view'], */
        ];
    }
}
