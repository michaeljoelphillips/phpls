<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function sprintf;

class FixtureTestCase extends TestCase
{
    protected const FIXTURE_DIRECTORY = __DIR__ . '/../fixtures/';

    protected function loadFixture(string $fixture): string
    {
        $fixture = file_get_contents(self::FIXTURE_DIRECTORY . $fixture);

        if ($fixture === false) {
            throw new InvalidArgumentException(sprintf('Could not find fixture with name %s', $fixture));
        }

        return $fixture;
    }
}
