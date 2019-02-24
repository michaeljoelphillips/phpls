<?php

declare(strict_types=1);

namespace LanguageServer\LSP\Command;

use LanguageServer\LSP\TextDocument;
use LanguageServer\LSP\TextDocumentRegistry;
use LanguageServer\RPC\Server;
use React\Stream\WritableStreamInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DidChange
{
    private $registry;

    public function __construct(Server $server, TextDocumentRegistry $registry)
    {
        $server->on('textDocument/didChange', [$this, 'handle']);

        $this->registry = $registry;
    }

    public function handle(object $request, WritableStreamInterface $output)
    {
        $document = new TextDocument(
            $request->params->textDocument->uri,
            $request->params->contentChanges[0]->text,
            $request->params->textDocument->version,
        );

        $this->registry->add($document);
    }
}
