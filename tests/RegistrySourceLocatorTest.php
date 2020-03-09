<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\RegistrySourceLocator;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;

class RegistrySourceLocatorTest extends TestCase
{
    private RegistrySourceLocator $subject;
    private TextDocumentRegistry $registry;

    public function setUp() : void
    {
        $astLocator     = $this->createMock(Locator::class);
        $this->registry = $this->createMock(TextDocumentRegistry::class);

        $this->subject = new RegistrySourceLocator($astLocator, $this->registry);
    }

    public function testLocateIdentifier() : void
    {
        $this
            ->registry
            ->expects($this->once())
            ->method('getAll')
            ->willReturn([]);

        $reflector = $this->createMock(Reflector::class);

        $this->subject->locateIdentifier($reflector, new Identifier('Foo', new IdentifierType()));
    }

    public function testLocateIdentifierWhenRegistryContainsAnEmptyTextDocument() : void
    {
        $this
            ->registry
            ->expects($this->once())
            ->method('getAll')
            ->willReturn([new TextDocument('file:///tmp/foo', '', 0)]);

        $reflector = $this->createMock(Reflector::class);

        $this->subject->locateIdentifier($reflector, new Identifier('Foo', new IdentifierType()));
    }

    public function testLocateIdentifiersByType() : void
    {
        $this
            ->registry
            ->expects($this->once())
            ->method('getAll')
            ->willReturn([]);

        $reflector = $this->createMock(Reflector::class);

        $this->subject->locateIdentifiersByType($reflector, new IdentifierType());
    }
}
