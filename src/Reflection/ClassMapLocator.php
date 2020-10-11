<?php

declare(strict_types=1);

namespace LanguageServer\Reflection;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AbstractSourceLocator;
use function array_key_exists;
use function file_exists;
use function file_get_contents;

class ClassMapLocator extends AbstractSourceLocator
{
    /** @var array<string, string> */
    private array $classMap;

    /**
     * @param array<string, string> $classMap
     */
    public function __construct(array $classMap, Locator $astLocator)
    {
        parent::__construct($astLocator);

        $this->classMap = $classMap;
    }

    public function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        if ($identifier->isClass() === false) {
            return null;
        }

        if (array_key_exists($identifier->getName(), $this->classMap) === false) {
            return null;
        }

        $file = $this->classMap[$identifier->getName()];

        if (file_exists($file) === false) {
            return null;
        }

        return new LocatedSource(file_get_contents($file), $file);
    }
}
