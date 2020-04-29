<?php

declare(strict_types=1);

namespace LanguageServer\Reflection;

use LanguageServer\ParsedDocument;
use LanguageServer\TextDocumentRegistry;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Throwable;
use function array_filter;
use function array_map;
use function array_values;

class RegistrySourceLocator implements SourceLocator
{
    private AstLocator $astLocator;
    private TextDocumentRegistry $registry;

    public function __construct(AstLocator $astLocator, TextDocumentRegistry $registry)
    {
        $this->astLocator = $astLocator;
        $this->registry   = $registry;
    }

    public function locateIdentifier(Reflector $reflector, Identifier $identifier) : ?Reflection
    {
        $aggregateLocator = $this->getAggregateLocator();

        return $aggregateLocator->locateIdentifier($reflector, $identifier);
    }

    /**
     * @return Reflection[]
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType) : array
    {
        $aggregateLocator = $this->getAggregateLocator();

        return $aggregateLocator->locateIdentifiersByType($reflector, $identifierType);
    }

    private function getAggregateLocator() : AggregateSourceLocator
    {
        $documents = $this->registry->getAll();

        $locators = array_map(
            function (ParsedDocument $document) {
                try {
                    return new StringSourceLocator($document->getSource(), $this->astLocator);
                } catch (Throwable $t) {
                    return null;
                }
            },
            $documents
        );

        return new AggregateSourceLocator(array_filter(array_values($locators)));
    }
}
