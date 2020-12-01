<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\MessageHandler\TextDocument;

use LanguageServer\Inference\TypeResolver;
use LanguageServer\MessageHandler\TextDocument\SignatureHelp;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Test\Unit\ParserTestCase;
use LanguageServer\TextDocumentRegistry;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

use function sprintf;

class SignatureHelpTest extends ParserTestCase
{
    private TextDocumentRegistry $registry;
    private SignatureHelp $subject;

    public function setUp(): void
    {
        $this->registry = new TextDocumentRegistry();
        $classReflector = $this->getClassReflector();
        $typeResolver   = new TypeResolver($classReflector);
        $this->subject  = new SignatureHelp($classReflector, $this->getFunctionReflector(), $typeResolver, $this->registry);
    }

    protected function getSourceLocator(): SourceLocator
    {
        return new SingleFileSourceLocator(
            sprintf('%s/SignatureHelpFixture.php', self::FIXTURE_DIRECTORY),
            $this->getAstLocator()
        );
    }

    public function testSignatureHelpReturnsEmptyResponseWhenNoExpressionFound(): void
    {
        $document = $this->parseFixture('SignatureHelpFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/signatureHelp', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 31,
                'character' => 9,
            ],
        ]);

        $next = function (): void {
            $this->fail('The next method should never be called');
        };

        $response = $this->subject->__invoke($request, $next);

        $this->assertEmpty($response->result->activeParameter);
    }

    public function testSignatureHelpReturnsEmptyResponseWhenNoConstructorFound(): void
    {
        $document = $this->parseFixture('SignatureHelpFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/signatureHelp', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 30,
                'character' => 33,
            ],
        ]);

        $next = function (): void {
            $this->fail('The next method should never be called');
        };

        $response = $this->subject->__invoke($request, $next);

        $this->assertEmpty($response->result->activeParameter);
    }

    /**
     * @dataProvider cursorPositionsProvider
     */
    public function testSignatureHelp(int $line, int $character, int $activeParameter, bool $signatureFound, ?string $label): void
    {
        $document = $this->parseFixture('SignatureHelpFixture.php');

        $this->registry->add($document);

        $request = new RequestMessage(1, 'textDocument/signatureHelp', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => $line,
                'character' => $character,
            ],
        ]);

        $next = function (): void {
            $this->fail('The next method should never be called');
        };

        $response = $this->subject->__invoke($request, $next);

        if ($signatureFound === false) {
            $this->assertCount(0, $response->result->signatures);

            return;
        }

        $this->assertCount(1, $response->result->signatures);
        $this->assertEquals(0, $response->result->activeSignature);
        $this->assertEquals($activeParameter, $response->result->activeParameter);
        $this->assertEquals($label, $response->result->signatures[0]->label);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function cursorPositionsProvider(): array
    {
        return [
            [18, 16, 0, false, null],
            [18, 17, 0, false, null],
            [18, 18, 0, true, 'stdClass $bar, array $baz'],
            [18, 19, 0, true, 'stdClass $bar, array $baz'],
            [18, 20, 0, false, null],
            [19, 16, 0, false, null],
            [19, 17, 0, false, null],
            [19, 18, 0, true, 'stdClass $bar, array $baz'],
            [19, 19, 0, true, 'stdClass $bar, array $baz'],
            [19, 20, 0, true, 'stdClass $bar, array $baz'],
            [19, 21, 0, true, 'stdClass $bar, array $baz'],
            [19, 33, 0, true, 'stdClass $bar, array $baz'],
            [19, 34, 1, true, 'stdClass $bar, array $baz'],
            [19, 35, 1, true, 'stdClass $bar, array $baz'],
            [19, 36, 1, true, 'stdClass $bar, array $baz'],
            [19, 37, 1, true, 'stdClass $bar, array $baz'],
            [19, 38, 0, false, null],
            [22, 26, 0, true, 'stdClass $bar, array $baz'],
            [23, 12, 1, true, 'stdClass $bar, array $baz'],
            [23, 13, 1, true, 'stdClass $bar, array $baz'],
            [27, 13, 0, true, 'stdClass $bar, array $baz'],
            [27, 14, 0, true, 'stdClass $bar, array $baz'],
            [27, 22, 0, true, '$baz'],
            [27, 23, 0, true, '$baz'],
            [27, 24, 0, true, '$baz'],
            [27, 25, 0, true, '$baz'],
            [27, 33, 0, true, 'stdClass $bar, array $baz'],
            [27, 34, 0, true, 'stdClass $bar, array $baz'],
            [27, 35, 0, true, '$baz'],
            [27, 36, 0, true, '$baz'],
            [27, 37, 0, true, '$baz'],
            [27, 38, 0, true, '$baz'],
            [27, 53, 0, true, '$baz'],
            [27, 54, 0, true, 'stdClass $bar, array $baz'],
            [33, 22, 0, false, null],
            [33, 23, 0, false, null],
            [33, 24, 0, false, null],
            [33, 25, 0, true, 'stdClass $bar, array $baz'],
            [33, 26, 0, true, 'stdClass $bar, array $baz'],
            [33, 27, 0, false, null],
            [33, 28, 0, false, null],
            [33, 29, 0, false, null],
            [33, 30, 0, false, null],
            [40, 26, 0, true, 'int $code, string $body'],
            [40, 27, 0, true, 'int $code, string $body'],
            /* [43, 20, 0, 'string $view'], */
        ];
    }
}
