<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics;

use LanguageServer\ParsedDocument;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

use function assert;

use const SIGTERM;

abstract class DiagnosticCommand
{
    private LoopInterface $loop;

    private string $cwd;

    private ?Process $runningProcess = null;

    public function __construct(LoopInterface $loop, string $cwd)
    {
        $this->loop = $loop;
        $this->cwd  = $cwd;
    }

    public function execute(ParsedDocument $document): PromiseInterface
    {
        $deferred             = new Deferred();
        $this->runningProcess = new Process($this->getCommand($document), $this->cwd);
        $input                = $this->input($document);

        $this->runningProcess->start($this->loop);

        assert($this->runningProcess->stdin instanceof WritableStreamInterface);
        assert($this->runningProcess->stdout instanceof ReadableStreamInterface);

        if ($input !== null) {
            $this->runningProcess->stdin->write($input);
            $this->runningProcess->stdin->end();
        }

        $output = '';
        $this->runningProcess->stdout->on('data', static function (string $data) use (&$output): void {
            $output .= $data;
        });

        $this->runningProcess->on('exit', static function (?int $code, ?int $term) use (&$output, &$deferred): void {
            if ($term !== null) {
                $deferred->reject();
            }

            $deferred->resolve($output);
        });

        return $deferred->promise();
    }

    public function isRunning(): bool
    {
        return $this->runningProcess !== null;
    }

    public function terminate(): void
    {
        if ($this->isRunning() === false) {
            return;
        }

        assert($this->runningProcess !== null);

        $this->runningProcess->terminate(SIGTERM);
    }

    protected function input(ParsedDocument $document): ?string
    {
        return null;
    }

    abstract protected function getCommand(ParsedDocument $document): string;
}
