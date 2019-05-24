<?php

declare(strict_types=1);

namespace Fixtures;

use Bar\Bar;
use Bar\Baz;

class Foo
{
    private $bar;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function testFunction()
    {
        return 'Hello';
    }

    public function anotherTestFunction(Baz $parameter)
    {
        $localVariable = new Baz();
        $localVariable->methodCall();

        $parameter->foo;

        return $this->testFunction();
    }
}
