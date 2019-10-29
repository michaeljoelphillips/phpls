<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DidSave
{
    private $registry;

    public function __construct(TextDocumentRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function __invoke(array $params)
    {
        $uri = $params['textDocument']['uri'];
        $document = new TextDocument($uri, $this->read($uri), 0);

        $this->registry->add($document);
    }

    private function read(string $uri): string
    {
        return file_get_contents($uri) ?: '';
    }
}
