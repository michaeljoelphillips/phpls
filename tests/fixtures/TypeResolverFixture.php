<?php

declare(strict_types=1);

namespace Fixtures;

use Foo\Bar as FooBar;
use Bar\Bar;
use Bar\Baz;
use Bar\Bag;

class Foo extends BaseClass
{
    private $bar;
    private FooBar $foobar;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function testFunction()
    {
        return 'Hello';
    }

    public function anotherTestFunction(Baz $parameter): Bar
    {
        $localVariable = new Baz();
        $localVariable->methodCall();

        $parameter->foo;

        $this->bar->baz;

        return $this->testFunction();
    }

    public function methodCallTestMethod()
    {
        $this->methodCallTestMethod()->bar;
    }

    public function methodWithoutReturnType()
    {
    }

    public function getFooBar(): FooBar
    {
        return new FooBar();
    }

    public function methodReturningSelf(): self
    {
    }

    public function methodReturningParent(): parent
    {
    }
}

abstract class BaseClass
{
}
