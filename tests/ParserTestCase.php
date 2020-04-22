<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\Parser\DocumentParser;
use LanguageServer\Parser\ParsedDocument;
use LanguageServer\TextDocument;
use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

abstract class ParserTestCase extends FixtureTestCase
{
    protected const FIXTURE_DIRECTORY = __DIR__ . '/fixtures';

    protected function getParser() : Parser
    {
        return (new ParserFactory())->create(
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
    }

    protected function getClassReflector() : ClassReflector
    {
        return new ClassReflector($this->getSourceLocator());
    }

    protected function getAstLocator() : AstLocator
    {
        return new AstLocator($this->getParser(), fn () => $this->getFunctionReflector());
    }

    private function getFunctionReflector() : FunctionReflector
    {
        return new FunctionReflector($this->getSourceLocator(), $this->getClassReflector());
    }

    protected function getSourceLocator() : SourceLocator
    {
        return new DirectoriesSourceLocator(
            [self::FIXTURE_DIRECTORY],
            $this->getAstLocator()
        );
    }

    protected function parse(string $file) : ParsedDocument
    {
        $parser = $this->getDocumentParser();

        $document = new TextDocument($file, $this->loadFixture($file), 0);

        return $parser->parse($document);
    }

    protected function getDocumentParser() : DocumentParser
    {
        return new DocumentParser($this->getParser());
    }
}
