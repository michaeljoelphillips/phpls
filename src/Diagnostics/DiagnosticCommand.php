<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics;

use React\Promise\PromiseInterface;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\Diagnostic;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\ChildProcess\Process;

abstract class DiagnosticCommand
{
    private LoopInterface $loop;

    private string $cwd;

    public function __construct(LoopInterface $loop, string $cwd)
    {
        $this->loop = $loop;
        $this->cwd  = $cwd;
    }

    public function execute(ParsedDocument $document): PromiseInterface
    {
        $deferred = new Deferred();
        $process  = new Process($this->getCommand($document), $this->cwd);
        $input    = $this->input($document);

        $process->start($this->loop);

        if ($input !== null) {
            $process->stdin->write($input);
            $process->stdin->end();
        }

        $output = '';
        $process->stdout->on('data', static function (string $data) use (&$output): void {
            $output .= $data;
        });

        $process->on('exit', function (int $code) use (&$output, &$deferred): void {
            $deferred->resolve($output);
        });

        return $deferred->promise();
    }

    protected function input(ParsedDocument $document): ?string
    {
        return null;
    }

    abstract protected function getCommand(ParsedDocument $document): string;
}
