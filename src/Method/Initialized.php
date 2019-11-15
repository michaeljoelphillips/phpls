<?php

namespace LanguageServer\Method;

use LanguageServer\Method\RemoteMethodInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Initialized implements RemoteMethodInterface
{
    public function __invoke(array $params)
    {
    }
}
