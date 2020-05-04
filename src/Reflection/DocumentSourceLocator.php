<?php

declare(strict_types=1);

namespace LanguageServer\Reflection;

use LanguageServer\ParsedDocument;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class DocumentSourceLocator extends StringSourceLocator
{
    private string $filename;

    public function __construct(ParsedDocument $document, Locator $astLocator)
    {
        parent::__construct($document->getSource(), $astLocator);

        $this->filename = $filename;
    }

    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        return new LocatedSource(
            $this->source,
            $this->filename
        );
    }
}
