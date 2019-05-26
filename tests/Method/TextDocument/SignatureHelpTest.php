<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument;

use LanguageServer\Method\TextDocument\SignatureHelp;
use LanguageServer\Parser\DocumentParser;
use LanguageServer\Test\ParserTestCase;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;
use LanguageServer\TypeResolver;
use PhpParser\Lexer;
use PhpParser\ParserFactory;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Throwable;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class SignatureHelpTest extends ParserTestCase
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

        $source = $this->loadFixture('SignatureHelpFixture.php');
        $locator = new StringSourceLocator($source, new AstLocator($phpParser));
        $reflector = new ClassReflector($locator);
        $resolver = new TypeResolver($reflector);
        $documentParser = new DocumentParser($phpParser);
        $registry = new TextDocumentRegistry();
        $document = new TextDocument('file:///tmp/foo.php', $source, 0);
        $registry->add($document);

        $this->subject = new SignatureHelp($reflector, $documentParser, $resolver, $registry);
    }

    /**
     * @dataProvider cursorPositions
     */
    public function testSignatureHelp(int $line, int $character, int $activeParameter, string $label)
    {
        $promise = $this->subject->__invoke([
            'textDocument' => [
                'uri' => 'file:///tmp/foo.php',
            ],
            'position' => [
                'line' => $line,
                'character' => $character,
            ],
        ]);

        $result = null;
        $promise->then(
            function ($value) use (&$result) {
                $result = $value;

                return;
            },
            function (Throwable $t) {
                $this->fail(sprintf('Failed to assert that the promise was fulfilled: %s', $t->getMessage()));
            }
        );

        $this->assertCount(1, $result->signatures);
        $this->assertEquals($activeParameter, $result->activeParameter);
        $this->assertEquals($label, $result->signatures[0]->label);
    }

    public function cursorPositions(): array
    {
        return [
            [17, 19, 0, 'stdClass $bar, array $baz'],
            [18, 36, 1, 'stdClass $bar, array $baz'],
            [19, 33, 0, 'stdClass $bar, array $baz'],
            [20, 16, 0, 'stdClass $bar, array $baz'],
            [22, 13, 1, 'stdClass $bar, array $baz'],
            [26, 25, 0, '$baz'],
            [26, 34, 0, 'stdClass $bar, array $baz'],
            [27, 13, 1, 'stdClass $bar, array $baz'],
        ];
    }
}
