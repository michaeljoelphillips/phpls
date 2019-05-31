<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument;

use LanguageServer\Method\TextDocument\SignatureHelp;
use LanguageServer\Parser\DocumentParser;
use LanguageServer\RegistrySourceLocator;
use LanguageServer\Test\FixtureTestCase;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;
use LanguageServer\TypeResolver;
use PhpParser\Lexer;
use PhpParser\ParserFactory;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class SignatureHelpTest extends FixtureTestCase
{
    private $subject;

    public function setUp(): void
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

        $registry = new TextDocumentRegistry();
        $locator = new RegistrySourceLocator(new AstLocator($phpParser), $registry);
        $reflector = new ClassReflector($locator);
        $resolver = new TypeResolver($reflector);
        $documentParser = new DocumentParser($phpParser);
        $document = new TextDocument('file:///tmp/foo.php', $this->loadFixture('SignatureHelpFixture.php'), 0);
        $registry->add($document);

        $this->subject = new SignatureHelp($reflector, $documentParser, $resolver, $registry);
    }

    /**
     * @dataProvider cursorPositionsProvider
     */
    public function testSignatureHelp(int $line, int $character, int $activeParameter, string $label)
    {
        $result = $this->subject->__invoke([
            'textDocument' => [
                'uri' => 'file:///tmp/foo.php',
            ],
            'position' => [
                'line' => $line,
                'character' => $character,
            ],
        ]);

        $this->assertCount(1, $result->signatures);
        $this->assertEquals($activeParameter, $result->activeParameter);
        $this->assertEquals($label, $result->signatures[0]->label);
    }

    public function testSignatureHelpReturnsEmptyResponseWhenNoExpressionFound()
    {
        $result = $this->subject->__invoke([
            'textDocument' => [
                'uri' => 'file:///tmp/foo.php',
            ],
            'position' => [
                'line' => 31,
                'character' => 9,
            ],
        ]);

        $this->assertEmpty($result->activeParameter);
    }

    public function testSignatureHelpReturnsEmptyResponseWhenNoConstructorFound()
    {
        $result = $this->subject->__invoke([
            'textDocument' => [
                'uri' => 'file:///tmp/foo.php',
            ],
            'position' => [
                'line' => 30,
                'character' => 33,
            ],
        ]);

        $this->assertEmpty($result->activeParameter);
    }

    public function cursorPositionsProvider(): array
    {
        return [
            [17, 19, 0, 'stdClass $bar, array $baz'],
            [18, 36, 1, 'stdClass $bar, array $baz'],
            [19, 33, 0, 'stdClass $bar, array $baz'],
            [20, 16, 0, 'stdClass $bar, array $baz'],
            [22, 13, 1, 'stdClass $bar, array $baz'],
            [26, 34, 0, 'stdClass $bar, array $baz'],
            [27, 13, 1, 'stdClass $bar, array $baz'],
            [26, 25, 0, '$baz'],
            [26, 53, 1, '$baz'],
        ];
    }
}
