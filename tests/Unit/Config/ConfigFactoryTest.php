<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Config;

use LanguageServer\Config\ConfigFactory;
use PHPUnit\Framework\TestCase;

use function array_merge;
use function chmod;
use function putenv;
use function sprintf;

class ConfigFactoryTest extends TestCase
{
    private const DEFAULT_CONFIG = [
        'log' => [
            'enabled' => false,
            'level' => 'info',
        ],
        'diagnostics' => [
            'enabled' => true,
            'ignore' => [],
            'php' => ['enabled' => true],
            'phpcs' => [
                'enabled' => false,
                'severity' => 'error',
            ],
            'phpstan' => [
                'enabled' => false,
                'severity' => 'error',
            ],
        ],

    ];

    public function testFactoryReturnsDefaultConfigWhenXDGConfigIsNotSet(): void
    {
        putenv('XDG_CONFIG_HOME');

        $subject = new ConfigFactory();

        $this->assertEquals(self::DEFAULT_CONFIG, $subject->__invoke());
    }

    public function testFactoryReturnsDefaultConfigWhenConfigDirectoryCannotBeLocated(): void
    {
        putenv('XDG_CONFIG_HOME=/tmp/empty-dir');

        $subject = new ConfigFactory();

        $this->assertEquals(self::DEFAULT_CONFIG, $subject->__invoke());
    }

    public function testFactoryReturnsDefaultConfigWhenNoConfigIsFound(): void
    {
        $emptyConfigDir = __DIR__ . '/../../fixtures/config-directories/empty-config';

        putenv(sprintf('XDG_CONFIG_HOME=%s', $emptyConfigDir));

        $subject = new ConfigFactory();

        $this->assertEquals(self::DEFAULT_CONFIG, $subject->__invoke());
    }

    public function testFactoryReturnsGlobalConfigWhenGlobalConfigExists(): void
    {
        $emptyConfigDir = __DIR__ . '/../../fixtures/config-directories/nonempty-config';

        putenv(sprintf('XDG_CONFIG_HOME=%s', $emptyConfigDir));

        $subject = new ConfigFactory();

        $config = array_merge(self::DEFAULT_CONFIG, [
            'log' => [
                'enabled' => true,
                'level' => 'info',
                'path' => '/tmp/log',
            ],
        ]);

        $this->assertEquals($config, $subject->__invoke());
    }

    public function testFactoryReturnsDefaultConfigWhenConfigIsNotReadable(): void
    {
        $emptyConfigDir = __DIR__ . '/../../fixtures/config-directories/unreadable-config';

        putenv(sprintf('XDG_CONFIG_HOME=%s', $emptyConfigDir));
        chmod(sprintf('%s/phpls/config.php', $emptyConfigDir), 200);

        $subject = new ConfigFactory();

        $this->assertEquals(self::DEFAULT_CONFIG, $subject->__invoke());
    }
}
