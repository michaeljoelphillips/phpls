<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use LanguageServer\ParsedDocument;
use LanguageServer\TextDocumentRegistry;
use LanguageServerProtocol\Diagnostic;

use function array_merge;
use function array_values;

class DiagnosticService implements EventEmitterInterface
{
    use EventEmitterTrait;

    /** @var array<int, DiagnosticRunner> */
    private array $runners;

    /**
     * @var array<string, array<string, array<string, Diagnostic>>>
     * @example ['uri' => [ 'diagnostic' => [], ]]
     **/
    private array $diagnostics = [];

    public function __construct(TextDocumentRegistry $registry, DiagnosticRunner ...$runners)
    {
        $this->runners = $runners;

        $registry->on('documentAdded', function (ParsedDocument $document): void {
            $this->diagnose($document);
        });
    }

    public function diagnose(ParsedDocument $document): void
    {
        foreach ($this->runners as $runner) {
            $runner
                ->run($document)
                ->then(
                    function (array $diagnostics) use ($runner, $document): void {
                        $uri            = $document->getUri();
                        $diagnosticName = $runner->getDiagnosticName();

                        $this->diagnostics[$uri][$diagnosticName] = $diagnostics;

                        $this->emit('notification', [
                            [
                                'uri' => $document->getUri(),
                                'diagnostics' => array_merge(...array_values($this->diagnostics[$uri])),
                            ],
                        ]);
                    }
                );
        }
    }
}
