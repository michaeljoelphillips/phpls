<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics\PhpStan;

use LanguageServer\Diagnostics\DiagnosticCommand as AbstractDiagnosticCommand;
use React\Promise\Deferred;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use LanguageServer\ParsedDocument;

class DiagnosticCommand extends AbstractDiagnosticCommand
{
    private const COMMAND_PARTS = [
        'executable' => 'php vendor/bin/phpstan analyse',
        'options' => '--no-progress --error-format=json --no-ansi',
        'file' => null,
    ];

    protected function getCommand(ParsedDocument $document): string
    {
        $file            = $document->getPath();
        $command         = self::COMMAND_PARTS;
        $command['file'] = $file;

        return implode(' ', $command);
    }
}
