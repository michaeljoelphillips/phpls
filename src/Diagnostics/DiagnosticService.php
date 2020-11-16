<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use LanguageServer\ParsedDocument;
use LanguageServer\Server\Protocol\NotificationMessage;
use LanguageServer\TextDocumentRegistry;
use LanguageServerProtocol\Diagnostic;

use function array_merge;
use function array_values;
use function strpos;

class DiagnosticService implements EventEmitterInterface
{
    use EventEmitterTrait;

    /** @var array<int, string> */
    private array $ignorePaths = [];

    /** @var array<int, Runner> */
    private array $runners;

    /**
     * @var array<string, array<string, array<string, Diagnostic>>>
     * @example ['uri' => [ 'diagnostic' => [], ]]
     **/
    private array $diagnostics = [];

    /**
     * @param array<int, string> $ignorePaths
     */
    public function __construct(TextDocumentRegistry $registry, array $ignorePaths, Runner ...$runners)
    {
        $this->runners     = $runners;
        $this->ignorePaths = $ignorePaths;

        $registry->on('documentAdded', function (ParsedDocument $document): void {
            $this->diagnose($document);
        });
    }

    public function diagnose(ParsedDocument $document): void
    {
        foreach ($this->ignorePaths as $ignorePath) {
            if (strpos($document->getUri(), $ignorePath) !== false) {
                return;
            }
        }

        foreach ($this->runners as $runner) {
            $runner
                ->run($document)
                ->then(
                    function (array $diagnostics) use ($runner, $document): void {
                        $uri            = $document->getUri();
                        $diagnosticName = $runner->getName();

                        $this->diagnostics[$uri][$diagnosticName] = $diagnostics;

                        $this->emit('notification', [
                            new NotificationMessage('textDocument/publishDiagnostics', [
                                'uri' => $uri,
                                'diagnostics' => array_merge(...array_values($this->diagnostics[$uri])),

                            ]),
                        ]);
                    }
                );
        }
    }
}
