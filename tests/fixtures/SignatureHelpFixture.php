<?php

namespace App;

use stdClass;
use function SignatureHelp\Functions\view;

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
            $this->bar($this->foo(), $baz, $bad, $qwe),
            []
        );

        new SignatureHelpFixture();

        return;
    }

    public function functionCompletion()
    {
        return \App\render();
    }

    public function importedFunctionCompletion()
    {
        return view();
    }
}

function render(int $code, string $body): Response
{
}
