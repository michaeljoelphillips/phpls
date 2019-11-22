<?php

namespace LanguageServer\Server;

use Closure;

interface RequestReaderInterface
{
    public function read(callable $callback): void;
}
