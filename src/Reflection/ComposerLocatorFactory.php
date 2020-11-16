<?php

declare(strict_types=1);

namespace LanguageServer\Reflection;

use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson;
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
    public function __invoke(string $path, Locator $astLocator): SourceLocator
    {
        $realPath = realpath($path) . '/';

        return new AggregateSourceLocator([
            $this->classMapLocator($realPath, $astLocator),
            $this->psr4AutoloadDevLocator($realPath, $astLocator),
            $this->psr4AutoloadLocator($realPath, $astLocator),
        ]);
    }

    private function psr4AutoloadLocator(string $path, Locator $astLocator): SourceLocator
    {
        return (new MakeLocatorForComposerJsonAndInstalledJson())($path, $astLocator);
    }

    private function psr4AutoloadDevLocator(string $root, Locator $astLocator): SourceLocator
    {
        $contents = file_get_contents($root . 'composer.json');

        if ($contents === false) {
            throw MissingComposerJson::inProjectPath($root);
        }

        $composerJson = json_decode($contents, true);

        $mappings = array_map(
            static fn (array $namespaces) => array_map(static fn ($path) => $root . $path, $namespaces),
            array_map(static fn ($path) => (array) $path, $composerJson['autoload-dev']['psr-4'])
        );

        return new PsrAutoloaderLocator(Psr4Mapping::fromArrayMappings($mappings), $astLocator);
    }

    private function classMapLocator(string $root, Locator $astLocator): SourceLocator
    {
        $classMap = require_once $root . 'vendor/composer/autoload_classmap.php';

        return new ClassMapLocator($classMap, $astLocator);
    }
}
