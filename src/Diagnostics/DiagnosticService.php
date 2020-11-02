<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use LanguageServer\ParsedDocument;
use LanguageServer\TextDocumentRegistry;

use function array_merge;
use function React\Promise\all;

class DiagnosticService implements EventEmitterInterface
{
    use EventEmitterTrait;

    /** @var array<int, DiagnosticRunner> */
    private array $runners;

    public function __construct(TextDocumentRegistry $registry, DiagnosticRunner ...$runners)
    {
        $this->runners = $runners;

        $registry->on('documentAdded', function (ParsedDocument $document): void {
            $this->diagnose($document);
        });
    }

    public function diagnose(ParsedDocument $document): void
    {
        $promises = [];

        foreach ($this->runners as $runner) {
            $promises[] = $runner($document);
        }

        $uri = $document->getUri();

        all($promises)->then(function (array $diagnostics) use ($uri): void {
            $diagnostics = array_merge(...$diagnostics);

            $this->emit('notification', [
                [
                    'diagnostics' => $diagnostics,
                    'uri' => $uri,
                ],
            ]);
        });
    }
}
