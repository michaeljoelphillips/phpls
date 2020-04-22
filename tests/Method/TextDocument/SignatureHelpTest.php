<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument;

use LanguageServer\Method\TextDocument\SignatureHelp;
use LanguageServer\Parser\DocumentParser;
use LanguageServer\RegistrySourceLocator;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Test\FixtureTestCase;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;
use LanguageServer\TypeResolver;
use PhpParser\Lexer;
use PhpParser\ParserFactory;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;

class SignatureHelpTest extends FixtureTestCase
{
    private SignatureHelp $subject;
    private RegistrySourceLocator $locator;
    private ClassReflector $classReflector;
    private ?FunctionReflector $functionReflector = null;

    public function setUp() : void
    {
        $phpParser = (new ParserFactory())->create(
            ParserFactory::PREFER_PHP7,
            new Lexer([
                'usedAttributes' => [
                    'comments',
                    'startLine',
                    'endLine',
                    'startFilePos',
                    'endFilePos',
                ],
            ])
        );

        $registry = $this->setUpRegistry();

        $this->locator = new RegistrySourceLocator(
            new AstLocator(
                $phpParser,
                function () {
                    return $this->functionReflector();
                }
            ),
            $registry
        );

        $this->classReflector = new ClassReflector($this->locator);
        $typeResolver         = new TypeResolver($this->classReflector);
        $documentParser       = new DocumentParser($phpParser);

        $this->subject = new SignatureHelp($this->classReflector, $this->functionReflector(), $documentParser, $typeResolver, $registry);
    }

    private function functionReflector() : FunctionReflector
    {
        if ($this->functionReflector) {
            return $this->functionReflector;
        }

        return $this->functionReflector = new FunctionReflector($this->locator, $this->classReflector);
    }

    private function setUpRegistry() : TextDocumentRegistry
    {
        $registry = new TextDocumentRegistry();

        $registry->add(
            new TextDocument(
                'file:///tmp/foo.php',
                $this->loadFixture('SignatureHelpFixture.php'),
                0
            )
        );

        $registry->add(
            new TextDocument(
                'file:///tmp/bar.php',
                $this->loadFixture('NamespacedFunctionsFixture.php'),
                0
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
