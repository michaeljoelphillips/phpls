<?php

namespace Fixtures;

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
