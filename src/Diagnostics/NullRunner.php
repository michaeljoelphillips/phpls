<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics;

use LanguageServer\ParsedDocument;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

class NullRunner implements Runner
{
    public function run(ParsedDocument $document): PromiseInterface
    {
        return reject();
    }

    public function getName(): string
    {
        return 'Null';
    }
}
