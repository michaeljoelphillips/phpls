<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics\PhpStan;

use LanguageServer\Diagnostics\Command as AbstractCommand;
use LanguageServer\ParsedDocument;

use function implode;

class Command extends AbstractCommand
{
    private const COMMAND_PARTS = [
        'executable' => 'php vendor/bin/phpstan analyse',
        'options' => '--no-progress --error-format=json --no-ansi',
        'file' => null,
    ];

    public function getCommand(ParsedDocument $document): string
    {
        $command         = self::COMMAND_PARTS;
        $command['file'] = $document->getPath();

        return implode(' ', $command);
    }
}
