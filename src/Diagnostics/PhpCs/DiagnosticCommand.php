<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics\PhpCs;

use LanguageServer\Diagnostics\DiagnosticCommand as AbstractDiagnosticCommand;
use React\Promise\Deferred;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use LanguageServer\ParsedDocument;

class DiagnosticCommand extends AbstractDiagnosticCommand
{
    private const COMMAND_PARTS = [
        'executable' => 'php vendor/bin/phpcs',
        'options' => '-q --report=json',
        'path' =>  '--stdin-path=',
        'file' => '-',
    ];

    protected function getCommand(ParsedDocument $document): string
    {
        $command         = self::COMMAND_PARTS;
        $command['path'] .= $document->getPath();

        return implode(' ', $command);
    }

    protected function input(ParsedDocument $document): ?string
    {
        return $document->getSource();
    }
}
