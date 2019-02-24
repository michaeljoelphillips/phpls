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
class DidOpen
{
    private $registry;

    public function __construct(Server $server, TextDocumentRegistry $registry)
    {
        $this->registry = $registry;

        $server->on('textDocument/didOpen', [$this, 'handle']);
    }

    public function handle(object $request, WritableStreamInterface $output)
    {
        $textDocument = new TextDocument(
            $request->params->textDocument->uri,
            $request->params->textDocument->text,
            $request->params->textDocument->version
        );

        $this->registry->add($textDocument);
    }
}
