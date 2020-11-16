<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics;

use LanguageServer\ParsedDocument;
use React\Promise\PromiseInterface;

interface Runner
{
    public function getName(): string;

    public function run(ParsedDocument $document): PromiseInterface;
}
