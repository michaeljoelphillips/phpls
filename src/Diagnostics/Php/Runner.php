<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics\Php;

use LanguageServer\Diagnostics\Runner as RunnerInterface;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use PhpParser\Error;
use React\Promise\PromiseInterface;

use function array_map;
use function React\Promise\resolve;

class Runner implements RunnerInterface
{
    private const RUNNER_NAME = 'PHP';

    public function getName(): string
    {
        return self::RUNNER_NAME;
    }

    public function run(ParsedDocument $document): PromiseInterface
    {
        return resolve(array_map(
            static function (Error $error) use ($document): Diagnostic {
                return new Diagnostic(
                    $error->getRawMessage(),
                    new Range(
                        new Position(
                            $error->getStartLine() - 1,
                            $error->getStartColumn($document->getSource()) - 1
                        ),
                        new Position(
                            $error->getEndLine() - 1,
                            $error->getEndColumn($document->getSource())
                        )
                    ),
                    500,
                    1,
                    'PHP'
                );
            },
            $document->getErrors()
        ));
    }
}
