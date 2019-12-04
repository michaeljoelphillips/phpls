<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Server\MessageHandlerInterface;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DidSave implements MessageHandlerInterface
{
    private TextDocumentRegistry $registry;

    public function __construct(TextDocumentRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function __invoke(Message $request, callable $next)
    {
        $uri = $request->params['textDocument']['uri'];
        $document = new TextDocument($uri, $this->read($uri), 0);

        $this->registry->add($document);
    }

    private function read(string $uri): string
    {
        return file_get_contents($uri) ?: '';
    }
}
