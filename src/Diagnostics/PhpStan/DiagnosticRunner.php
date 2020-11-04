<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics\PhpStan;

use LanguageServer\Diagnostics\DiagnosticRunner as DiagnosticRunnerInterface;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use React\Promise\PromiseInterface;

use function array_map;
use function array_merge;
use function array_values;
use function json_decode;
use function React\Promise\reject;

class DiagnosticRunner implements DiagnosticRunnerInterface
{
    private const RUNNER_NAME = 'PHPStan';

    private DiagnosticCommand $command;

    public function __construct(DiagnosticCommand $command)
    {
        $this->command = $command;
    }

    public function getDiagnosticName(): string
    {
        return self::RUNNER_NAME;
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
            ->then(
                function (string $output): array {
                    return $this->buildDiagnostics($output);
                }
            );
    }

    /**
     * @return array<int, Diagnostic>
     */
    private function buildDiagnostics(string $output): array
    {
        $output = json_decode($output, true);

        if ($output['totals']['file_errors'] === 0) {
            return [];
        }

        $errors = array_values($output['files']);
        $errors = array_merge(...$errors);

        return array_map(
            static function (array $error): Diagnostic {
                return new Diagnostic(
                    $error['message'],
                    new Range(
                        new Position($error['line'] - 1, -1),
                        new Position($error['line'] - 1, -1)
                    ),
                    500,
                    1,
                    self::RUNNER_NAME
                );
            },
            $errors['messages']
        );
    }
}
