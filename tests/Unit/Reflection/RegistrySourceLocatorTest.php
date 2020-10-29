<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Reflection;

use LanguageServer\ParsedDocument;
use LanguageServer\Reflection\RegistrySourceLocator;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;

class RegistrySourceLocatorTest extends TestCase
{
    private const EMPTY_FILE_FIXTURE = __DIR__ . '/../../fixtures/EmptyFileFixture.php';

    private RegistrySourceLocator $subject;
    private TextDocumentRegistry $registry;

    public function setUp(): void
    {
        $astLocator     = $this->createMock(Locator::class);
        $this->registry = $this->createMock(TextDocumentRegistry::class);

        $this->subject = new RegistrySourceLocator($astLocator, $this->registry);
    }

    public function testLocateIdentifier(): void
    {
        $this
            ->registry
            ->expects($this->once())
            ->method('getAll')
            ->willReturn([]);

        $reflector = $this->createMock(Reflector::class);

        $this->subject->locateIdentifier($reflector, new Identifier('Foo', new IdentifierType()));
    }

    public function testLocateIdentifierWhenRegistryContainsAnEmptyTextDocument(): void
    {
        $this
            ->registry
            ->expects($this->once())
            ->method('getAll')
            ->willReturn([new ParsedDocument(self::EMPTY_FILE_FIXTURE, '', [])]);

        $reflector = $this->createMock(Reflector::class);

        $this->subject->locateIdentifier($reflector, new Identifier('Foo', new IdentifierType()));
    }

    public function testLocateIdentifiersByType(): void
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
