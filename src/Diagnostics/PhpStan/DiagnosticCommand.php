<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics\PhpStan;

use LanguageServer\Diagnostics\DiagnosticCommand as AbstractDiagnosticCommand;
use LanguageServer\ParsedDocument;

use function assert;
use function file_put_contents;
use function implode;
use function is_string;
use function sys_get_temp_dir;
use function tempnam;

class DiagnosticCommand extends AbstractDiagnosticCommand
{
    private const COMMAND_PARTS = [
        'executable' => 'php vendor/bin/phpstan analyse',
        'options' => '--no-progress --error-format=json --no-ansi',
        'file' => null,
    ];

    protected function getCommand(ParsedDocument $document): string
    {
        $name = tempnam(sys_get_temp_dir(), 'phpstanls');
        assert(is_string($name));
        file_put_contents($name, $document->getSource());

        $file            = $name;
        $command         = self::COMMAND_PARTS;
        $command['file'] = $file;

        return implode(' ', $command);
    }
}
