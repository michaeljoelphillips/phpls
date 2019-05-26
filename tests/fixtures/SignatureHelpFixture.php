<?php

namespace App;

use stdClass;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class SignatureHelpFixture
{
    public function foo(stdClass $bar, array $baz)
    {
    }

    public function bar($baz = null)
    {
        $this->foo();
        $this->foo(new stdClass(), []);
        $this->foo(new stdClass());
        $this->foo(
            new stdClass(),
            []
        );

        $this->foo(
            $this->bar($this->foo()),
            []
        );

        return;
    }
}
