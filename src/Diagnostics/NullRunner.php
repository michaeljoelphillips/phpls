<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics;

use LanguageServer\ParsedDocument;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

class NullRunner implements DiagnosticRunner
{
    private const RUNNER_NAME = 'Null';

    public function run(ParsedDocument $document): PromiseInterface
    {
        return reject();
    }

    public function getDiagnosticName(): string
    {
        return self::RUNNER_NAME;
    }
}
