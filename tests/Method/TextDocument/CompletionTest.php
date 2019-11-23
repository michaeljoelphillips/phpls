<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument;

use LanguageServer\Method\TextDocument\Completion;
use LanguageServer\Method\TextDocument\CompletionProvider\CompletionProviderInterface;
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
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class CompletionTest extends FixtureTestCase
{
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

        $documentParser = new DocumentParser($phpParser);
        $registry = new TextDocumentRegistry();
        $locator = new RegistrySourceLocator(new AstLocator($phpParser), $registry);
        $reflector = new ClassReflector($locator);
        $resolver = new TypeResolver($reflector);
        $provider = $this->createMock(CompletionProviderInterface::class);
        $source = $this->loadFixture('SignatureHelpFixture.php');
        $textDocument = new TextDocument('file:///tmp/foo.php', $source, 0);
        $registry->add($textDocument);

        $this->subject = new Completion(
            $documentParser,
            $registry,
            $reflector,
            $resolver,
            $provider
        );
    }

    public function testCompletion()
    {
        $result = $this->subject->__invoke(new RequestMessage(1, 'textDocument/completion', [
            'textDocument' => [
                'uri' => 'file:///tmp/foo.php',
            ],
            'position' => [
                'line' => 17,
                'character' => 16,
            ],
        ]));
    }
}
