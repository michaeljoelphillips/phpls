<?php

declare(strict_types=1);

namespace LanguageServer\Test\Config;

use PHPUnit\Framework\TestCase;
use LanguageServer\Config\ConfigFactory;

class ConfigFactoryTest extends TestCase
{
    public function testFactoryReturnsDefaultConfigWhenXDGConfigIsNotSet(): void
    {
        putenv('XDG_CONFIG_HOME');

        $subject = new ConfigFactory();

        $this->assertEquals(
            [
                'log' => [
                    'enabled' => false,
                ]
            ],
            $subject->__invoke()
        );
    }

    public function testFactoryReturnsDefaultConfigWhenConfigDirectoryCannotBeLocated(): void
    {
        putenv('XDG_CONFIG_HOME=/tmp/empty-dir');

        $subject = new ConfigFactory();

        $this->assertEquals(
            [
                'log' => [
                    'enabled' => false,
                ]
            ],
            $subject->__invoke()
        );
    }

    public function testFactoryReturnsDefaultConfigWhenNoConfigIsFound(): void
    {
        $emptyConfigDir = __DIR__.'/../fixtures/config-directories/empty-config';

        putenv("XDG_CONFIG_HOME=$emptyConfigDir");

        $subject = new ConfigFactory();

        $this->assertEquals(
            [
                'log' => [
                    'enabled' => false,
                ]
            ],
            $subject->__invoke()
        );
    }

    public function testFactoryReturnsGlobalConfigWhenGlobalConfigExists(): void
    {
        $emptyConfigDir = __DIR__.'/../fixtures/config-directories/nonempty-config';

        putenv("XDG_CONFIG_HOME=$emptyConfigDir");

        $subject = new ConfigFactory();

        $this->assertEquals(
            [
                'log' => [
                    'enabled' => true,
                    'level' => 'info',
                    'path' => '/tmp/log',
                ]
            ],
            $subject->__invoke()
        );
    }

    public function testFactoryReturnsDefaultConfigWhenConfigIsNotReadable(): void
    {
        $emptyConfigDir = __DIR__.'/../fixtures/config-directories/unreadable-config';

        putenv("XDG_CONFIG_HOME=$emptyConfigDir");

        chmod(sprintf('%s/phpls/config.php', $emptyConfigDir), 200);

        $subject = new ConfigFactory();

        $this->assertEquals(
            [
                'log' => [
                    'enabled' => false,
                ]
            ],
            $subject->__invoke()
        );
    }
}