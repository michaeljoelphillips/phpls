<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics\PhpCs;

use LanguageServer\Diagnostics\DiagnosticRunner as DiagnosticRunnerInterface;
use React\Promise\PromiseInterface;
use LanguageServer\ParsedDocument;

use function React\Promise\resolve;
use function json_decode;
use LanguageServer\Diagnostics\DiagnosticCommand;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\DiagnosticSeverity;

class DiagnosticRunner implements DiagnosticRunnerInterface
{
    private const RUNNER_NAME = 'PHPCS';

    private DiagnosticCommand $command;

    private bool $isRunning = false;

    public function __construct(DiagnosticCommand $command)
    {
        $this->command = $command;
    }

    public function __invoke(ParsedDocument $document): PromiseInterface
    {
        if ($document->hasErrors() || $this->isRunning) {
            return resolve([]);
        }

        $this->isRunning = true;

        return $this
            ->command
            ->execute($document)
            ->then(function (string $output): array {
                $this->isRunning = false;

                return $this->handleOutput($output);
            });
    }

    /**
     * @return array<int, Diagnostic>
     */
    private function handleOutput(string $output): array
    {
        $output = json_decode($output, true);

        if ($output['totals']['errors'] === 0) {
            return [];
        }

        $errors = array_values($output['files']);
        $errors = array_merge(...$errors);

        return array_map(
            static function (array $error): Diagnostic {
                return new Diagnostic(
                    $error['message'],
                    new Range(
                        new Position($error['line'] - 1, 0),
                        new Position($error['line'] - 1, 0)
                    ),
                    500,
                    DiagnosticSeverity::WARNING,
                    self::RUNNER_NAME
                );
            },
            $errors['messages']
        );
    }
}
