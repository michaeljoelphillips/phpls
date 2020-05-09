<?php

declare(strict_types=1);

namespace LanguageServer\Reflection;

use LanguageServer\ParsedDocument;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AbstractSourceLocator;

class DocumentSourceLocator extends AbstractSourceLocator
{
    private ParsedDocument $document;

    public function __construct(ParsedDocument $document, Locator $astLocator)
    {
        parent::__construct($astLocator);

        $this->document = $document;
    }

    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        return new LocatedSource(
            $this->document->getSource(),
            $this->document->getUri()
        );
    }
}
