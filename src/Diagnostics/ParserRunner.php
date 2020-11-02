<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics;

use LanguageServer\ParsedDocument;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use PhpParser\Error;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

use function array_map;

class ParserRunner implements DiagnosticRunner
{
    public function __invoke(ParsedDocument $document): PromiseInterface
    {
        $deferred = new Deferred();

        $deferred->resolve(array_map(
            static function (Error $error): Diagnostic {
                return new Diagnostic(
                    $error->getRawMessage(),
                    new Range(
                        new Position($error->getStartLine() - 1, 0),
                        new Position($error->getEndLine() - 1, 0)
                    ),
                    500,
                    1,
                    'PHP'
                );
            },
            $document->getErrors()
        ));

        return $deferred->promise();
    }
}
