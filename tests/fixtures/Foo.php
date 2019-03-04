<?php

namespace Fixtures;

use Bar\Baz;

class Foo
{
    public function testFunction()
    {
        return 'Hello';
    }

    public function anotherTestFunction()
    {
        return $this->testFunction();
    }
}
