<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit;

use FilesystemIterator;
use LanguageServer\ParsedDocument;
use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Type\FileIteratorSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

abstract class ParserTestCase extends FixtureTestCase
{
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

    protected function getFunctionReflector() : FunctionReflector
    {
        return new FunctionReflector($this->getSourceLocator(), $this->getClassReflector());
    }

    protected function getSourceLocator() : SourceLocator
    {
        return new FileIteratorSourceLocator(
            new FilesystemIterator(self::FIXTURE_DIRECTORY, FilesystemIterator::SKIP_DOTS),
            $this->getAstLocator()
        );
    }

    protected function parse(string $file) : ParsedDocument
    {
        $parser = $this->getParser();
        $source = $this->loadFixture($file);
        $nodes  = $parser->parse($source);

        return new ParsedDocument($file, $source, $nodes);
    }
}