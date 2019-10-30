<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use LanguageServer\RegistrySourceLocator;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use LanguageServer\TextDocument;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class RegistrySourceLocatorTest extends TestCase
{
    private $subject;
    private $registry;

    public function setUp(): void
    {
        $astLocator = $this->createMock(Locator::class);
        $this->registry = $this->createMock(TextDocumentRegistry::class);

        $this->subject = new RegistrySourceLocator($astLocator, $this->registry);
    }

    public function testLocateIdentifier()
    {
        $this
            ->registry
            ->expects($this->once())
            ->method('getAll')
            ->willReturn([]);

        $reflector = $this->createMock(Reflector::class);

        $this->subject->locateIdentifier($reflector, new Identifier('Foo', new IdentifierType()));
    }

    public function testLocateIdentifierWhenRegistryContainsAnEmptyTextDocument()
    {
        $this
            ->registry
            ->expects($this->once())
            ->method('getAll')
            ->willReturn([new TextDocument('file:///tmp/foo', '', 0)]);

        $reflector = $this->createMock(Reflector::class);

        $this->subject->locateIdentifier($reflector, new Identifier('Foo', new IdentifierType()));
    }

    public function testLocateIdentifiersByType()
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
