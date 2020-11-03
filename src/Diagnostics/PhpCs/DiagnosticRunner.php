<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics\PhpCs;

use LanguageServer\Diagnostics\DiagnosticCommand;
use LanguageServer\Diagnostics\DiagnosticRunner as DiagnosticRunnerInterface;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\DiagnosticSeverity;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use React\Promise\PromiseInterface;

use function array_map;
use function array_merge;
use function array_values;
use function json_decode;

class DiagnosticRunner implements DiagnosticRunnerInterface
{
    private const RUNNER_NAME = 'PHPCS';

    private DiagnosticCommand $command;

    /** @var array<int, Diagnostic> */
    private array $diagnostics = [];

    public function __construct(DiagnosticCommand $command)
    {
        $this->command = $command;
    }

    public function __invoke(ParsedDocument $document): PromiseInterface
    {
        if ($this->command->isRunning()) {
            $this->command->terminate();
        }

        return $this
            ->command
            ->execute($document)
            ->then(
                function (string $output): array {
                    $this->diagnostics = $this->gatherDiagnostics($output);

                    return $this->diagnostics;
                },
                function (): array {
                    return $this->diagnostics;
                }
            );
    }

    /**
     * @return array<int, Diagnostic>
     */
    private function gatherDiagnostics(string $output): array
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
                        new Position($error['line'] - 1, $error['column'] - 1),
                        new Position($error['line'] - 1, -1)
                    ),
                    $error['severity'],
                    DiagnosticSeverity::ERROR,
                    self::RUNNER_NAME
                );
            },
            $errors['messages']
        );
    }
}
