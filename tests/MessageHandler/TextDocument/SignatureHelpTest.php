<?php

declare(strict_types=1);

namespace LanguageServer\Test\MessageHandler\TextDocument;

use LanguageServer\Inference\TypeResolver;
use LanguageServer\MessageHandler\TextDocument\SignatureHelp;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Test\ParserTestCase;
use LanguageServer\TextDocumentRegistry;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use function sprintf;

class SignatureHelpTest extends ParserTestCase
{
    private TextDocumentRegistry $registry;
    private SignatureHelp $subject;

    public function setUp() : void
    {
        $this->registry = new TextDocumentRegistry();
        $classReflector = $this->getClassReflector();
        $typeResolver   = new TypeResolver($classReflector);
        $this->subject  = new SignatureHelp($classReflector, $this->getFunctionReflector(), $typeResolver, $this->registry);
    }

    protected function getSourceLocator() : SourceLocator
    {
        return new SingleFileSourceLocator(
            sprintf('%s/SignatureHelpFixture.php', self::FIXTURE_DIRECTORY),
            $this->getAstLocator()
        );
    }

    /**
     * @dataProvider cursorPositionsProvider
     */
    public function testSignatureHelp(int $line, int $character, int $activeParameter, string $label) : void
    {
        $document = $this->parse('SignatureHelpFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/signatureHelp', [
            'textDocument' => ['uri' => $document->getUri()],
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
        $document = $this->parse('SignatureHelpFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/signatureHelp', [
            'textDocument' => ['uri' => $document->getUri()],
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
        $document = $this->parse('SignatureHelpFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/signatureHelp', [
            'textDocument' => ['uri' => $document->getUri()],
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
