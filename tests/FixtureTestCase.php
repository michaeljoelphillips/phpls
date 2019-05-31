<?php

declare(strict_types=1);

namespace LanguageServer\Test;

use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class FixtureTestCase extends TestCase
{
    protected function loadFixture(string $fixture): string
    {
        return file_get_contents(__DIR__.'/fixtures/'.$fixture);
    }
}
