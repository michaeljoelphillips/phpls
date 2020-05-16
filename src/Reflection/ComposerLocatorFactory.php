<?php

declare(strict_types=1);

namespace LanguageServer\Reflection;

use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForComposerJsonAndInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\PsrAutoloaderLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use function array_map;
use function file_get_contents;
use function json_decode;
use function realpath;

class ComposerLocatorFactory
{
    public function __invoke(string $path, Locator $astLocator) : SourceLocator
    {
        $composerLocator = (new MakeLocatorForComposerJsonAndInstalledJson())($path, $astLocator);

        $realPath = realpath($path) . '/';

        return new AggregateSourceLocator([
            $composerLocator,
            new PsrAutoloaderLocator(
                Psr4Mapping::fromArrayMappings($this->requireDevMappings($realPath)),
                $astLocator
            ),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function requireDevMappings(string $root) : array
    {
        $composerJson = json_decode(file_get_contents($root . 'composer.json'), true);

        return array_map(
            static fn (array $namespaces) => array_map(static fn ($path) => $root . $path, $namespaces),
            array_map(static fn ($path) => (array) $path, $composerJson['autoload-dev']['psr-4'])
        );
    }
}
