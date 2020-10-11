<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit;

use PHPUnit\Framework\TestCase;
use function file_get_contents;

class FixtureTestCase extends TestCase
{
    protected const FIXTURE_DIRECTORY = __DIR__ . '/../fixtures/';

    protected function loadFixture(string $fixture) : string
    {
        return file_get_contents(self::FIXTURE_DIRECTORY . $fixture);
    }
}
