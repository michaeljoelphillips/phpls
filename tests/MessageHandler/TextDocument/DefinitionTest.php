<?php

declare(strict_types=1);

namespace LanguageServer\Tests\MessageHandler\TextDocument;

use LanguageServer\Inference\TypeResolver;
use LanguageServer\MessageHandler\TextDocument\Definition;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Test\ParserTestCase;
use LanguageServer\TextDocumentRegistry;
use Psr\Log\LoggerInterface;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use function sprintf;

class DefinitionTest extends ParserTestCase
{
    private TextDocumentRegistry $registry;
    private Definition $subject;

    public function setUp() : void
    {
        $this->registry = new TextDocumentRegistry();
        $reflector      = $this->getClassReflector();
        $typeResolver   = new TypeResolver($reflector);

        $this->subject = new Definition(
            $this->registry,
            $typeResolver,
            $reflector,
            $this->createMock(LoggerInterface::class)
        );
    }

    protected function getSourceLocator() : SourceLocator
    {
        return new AggregateSourceLocator([
            new SingleFileSourceLocator(
                sprintf('%s/DefinitionFixture.php', self::FIXTURE_DIRECTORY),
                $this->getAstLocator()
            ),
            new SingleFileSourceLocator(
                sprintf('%s/TextDocumentFixture.php', self::FIXTURE_DIRECTORY),
                $this->getAstLocator()
            ),
        ]);
    }

    public function testDefinition() : void
    {
        $document = $this->parse('DefinitionFixture.php');

        $this->registry->add($document);

        $message = new RequestMessage(1, 'textDocument/definition', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 10,
                'character' => 25,
            ],
        ]);

        $response = $this->subject->__invoke($message, fn () => $this->fail('Should never be called'));

        $this->assertStringContainsString('TextDocumentFixture.php', $response->result->uri);
    }

    public function testDefinitionReturnsNullWhenTypeDoesNotExist() : void
    {
        $document = $this->parse('DefinitionFixture.php');

        $this->registry->add($document);

        $message = new RequestMessage(1, 'textDocument/definition', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 11,
                'character' => 25,
            ],
        ]);

        $response = $this->subject->__invoke($message, fn () => $this->fail('Should never be called'));

        $this->assertNull($response->result);
    }

    public function testDefinitionReturnsNullWhenNodeIsNotAnIdentifier() : void
    {
        $document = $this->parse('DefinitionFixture.php');

        $this->registry->add($document);

        $message = new RequestMessage(1, 'textDocument/definition', [
            'textDocument' => ['uri' => $document->getUri()],
            'position' => [
                'line' => 13,
                'character' => 25,
            ],
        ]);

        $response = $this->subject->__invoke($message, fn () => $this->fail('Should never be called'));

        $this->assertNull($response->result);
    }
}
