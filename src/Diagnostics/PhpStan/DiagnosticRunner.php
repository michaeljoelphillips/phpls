<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics\PhpStan;

use LanguageServer\ParsedDocument;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use Psr\Log\LoggerInterface;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use LanguageServer\Diagnostics\DiagnosticRunner as DiagnosticRunnerInterface;

use function json_decode;
use function property_exists;
use function React\Promise\resolve;

class DiagnosticRunner implements DiagnosticRunnerInterface
{
    private const RUNNER_NAME = 'PHPStan';

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
                        new Position($error['line'] - 1, 0),
                        new Position($error['line'] - 1, 0)
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
