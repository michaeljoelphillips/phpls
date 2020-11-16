<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics;

use LanguageServer\ParsedDocument;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\DiagnosticSeverity;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use UnexpectedValueException;

use function React\Promise\reject;

abstract class AsyncCommandRunner implements Runner
{
    private Command $command;

    protected LoggerInterface $logger;

    protected int $severity;

    public function __construct(Command $command, LoggerInterface $logger, string $severity)
    {
        if ($severity !== 'error' && $severity !== 'warning') {
            throw new UnexpectedValueException();
        }

        $this->command  = $command;
        $this->logger   = $logger;
        $this->severity = $severity === 'error' ? DiagnosticSeverity::ERROR : DiagnosticSeverity::WARNING;
    }

    public function run(ParsedDocument $document): PromiseInterface
    {
        if ($document->hasErrors() || $document->isPersisted() === false) {
            return reject();
        }

        if ($this->command->isRunning()) {
            $this->command->terminate();
        }

        return $this
            ->command
            ->execute($document)
            ->then(fn (string $output): array => $this->gatherDiagnostics($document, $output));
    }

    /**
     * @param string $output The command's output from `stdout`
     *
     * @return array<int, Diagnostic>
     */
    abstract protected function gatherDiagnostics(ParsedDocument $document, string $output): array;
}
