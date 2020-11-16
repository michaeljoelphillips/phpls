<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics\PhpCs;

use LanguageServer\Diagnostics\Command as AbstractCommand;
use LanguageServer\ParsedDocument;

use function implode;

class Command extends AbstractCommand
{
    private const COMMAND_PARTS = [
        'executable' => 'php vendor/bin/phpcs',
        'options' => '-q --report=json --no-colors',
        'path' =>  '--stdin-path=',
        'file' => '-',
    ];

    public function getCommand(ParsedDocument $document): string
    {
        $command          = self::COMMAND_PARTS;
        $command['path'] .= $document->getPath();

        return implode(' ', $command);
    }

    protected function input(ParsedDocument $document): ?string
    {
        return $document->getSource();
    }
}
