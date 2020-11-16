<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics\PhpCs;

use JsonException;
use LanguageServer\Diagnostics\AsyncCommandRunner;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;

use function array_map;
use function array_merge;
use function array_values;
use function json_decode;

use const JSON_THROW_ON_ERROR;

class Runner extends AsyncCommandRunner
{
    public function getName(): string
    {
        return 'PHPCS';
    }

    /**
     * {@inheritdoc}
     */
    protected function gatherDiagnostics(ParsedDocument $document, string $output): array
    {
        try {
            $output = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

            if ($output['totals']['errors'] === 0) {
                return [];
            }

            $errors = array_values($output['files']);
            $errors = array_merge(...$errors);

            return array_map(
                function (array $error) use ($document): Diagnostic {
                    [, $endColumn] = $document->getColumnPositions($error['line'] - 1);

                    return new Diagnostic(
                        $error['message'],
                        new Range(
                            new Position($error['line'] - 1, $error['column'] - 1),
                            new Position($error['line'] - 1, $endColumn)
                        ),
                        $error['severity'],
                        $this->severity,
                        $this->getName()
                    );
                },
                $errors['messages']
            );
        } catch (JsonException $e) {
            $this->logger->error('PHPCS failed unexpectedly', [
                'exception' => $e,
                'output' => $output,
            ]);

            return [];
        }
    }
}
