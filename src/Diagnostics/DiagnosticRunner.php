<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics;

use LanguageServer\ParsedDocument;
use React\Promise\PromiseInterface;

interface DiagnosticRunner
{
    public function __invoke(ParsedDocument $document): PromiseInterface;
}
