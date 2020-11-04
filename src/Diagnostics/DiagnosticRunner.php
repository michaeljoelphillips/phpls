<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics;

use LanguageServer\ParsedDocument;
use React\Promise\PromiseInterface;

interface DiagnosticRunner
{
    public function run(ParsedDocument $document): PromiseInterface;

    public function getDiagnosticName(): string;
}
