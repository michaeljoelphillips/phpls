<?php

declare(strict_types=1);

namespace LanguageServer\Method;

use LanguageServer\Method\RemoteMethodInterface;
use LanguageServer\TextDocumentRegistry;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Exit_ implements RemoteMethodInterface
{
    public function __invoke(array $params)
    {
        exit(0);
    }
}
