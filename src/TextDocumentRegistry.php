<?php

declare(strict_types=1);

namespace LanguageServer;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class TextDocumentRegistry
{
    /** @var TextDocument[] */
    private $documents = [];

    /**
     * Get a document by filename and version.
     *
     * @param string $fileName
     * @param int    $version
     *
     * @return TextDocument
     */
    public function get(string $fileName): TextDocument
    {
        return $this->documents[$fileName];
    }

    /**
     * Add a document to the registry.
     *
     * @param TextDocument $document
     */
    public function add(TextDocument $document): void
    {
        $this->documents[$document->getPath()] = $document;
    }

    public function getAll(): array
    {
        return $this->documents;
    }
}
