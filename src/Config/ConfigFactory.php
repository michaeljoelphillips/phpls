<?php

declare(strict_types=1);

namespace LanguageServer\Config;

use Symfony\Component\Config\Definition\Processor;
use LanguageServer\Config\ServerConfiguration;

class ConfigFactory
{
    private const PHPLS_CONFIG_PATH = 'phpls'.DIRECTORY_SEPARATOR.'config.php';
    private const DEFAULT_CONFIG = [
        'log' => [
            'enabled' => false,
        ],
    ];

    public function __invoke(): array
    {
        return (new Processor())->processConfiguration(
            new ServerConfiguration(),
            [
                self::DEFAULT_CONFIG,
                $this->globalConfig()
            ]
        );
    }

    private function globalConfig(): array
    {
        $globalConfigPath = $this->globalConfigPath();

        if ($globalConfigPath === null) {
            return [];
        }

        if ($this->fileExists($globalConfigPath) === false) {
            return [];
        }

        return require_once($globalConfigPath);
    }

    private function globalConfigPath(): ?string
    {
        $configDir = getenv('XDG_CONFIG_HOME') ?: null;

        if ($configDir === null) {
            return null;
        }

        return $configDir.DIRECTORY_SEPARATOR.self::PHPLS_CONFIG_PATH;
    }

    private function fileExists(string $config): bool
    {
        if (file_exists($config) === false) {
            return false;
        }

        if (is_readable($config) === false) {
            return false;
        }

        return true;
    }
}
