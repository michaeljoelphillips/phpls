<?php

declare(strict_types=1);

namespace App;

class CompletionProviderFixture
{
    public function foo()
    {
        $this->stub;
    }

    public function bar()
    {
        $this->foo()->stub;
    }

    public function foobar()
    {
        return;
    }

    public function baz()
    {
        return $this->fooba;
    }
}
