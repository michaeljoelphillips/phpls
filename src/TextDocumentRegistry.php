<?php

declare(strict_types=1);

namespace LanguageServer;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;

class TextDocumentRegistry implements EventEmitterInterface
{
    use EventEmitterTrait;

    /** @var array<string, ParsedDocument> */
    private array $documents = [];

    public function get(string $fileName): ParsedDocument
    {
        return $this->documents[$fileName];
    }

    public function add(ParsedDocument $document): void
    {
        $this->documents[$document->getUri()] = $document;

        $this->emit('documentAdded', [$document]);
    }

    /**
     * @return array<string, ParsedDocument>
     */
    public function getAll(): array
    {
        return $this->documents;
    }

    public function clear(): void
    {
        $this->documents = [];
    }
}
