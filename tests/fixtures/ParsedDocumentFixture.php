<?php

namespace Fixtures;

use Psr\Log\LoggerInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ParsedDocumentFixture
{
    public function testMethod()
    {
    }

    public function anotherTestMethod()
    {
        $result = $this->testMethod();

        return $result;
    }

    public function getFoo() : Foo
    {
        return new Foo();
    }
}
