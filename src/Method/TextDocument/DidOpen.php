<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Parser\DocumentParser;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;

class DidOpen implements MessageHandler
{
    private TextDocumentRegistry $registry;
    private DocumentParser $parser;

    public function __construct(TextDocumentRegistry $registry, DocumentParser $parser)
    {
        $this->registry = $registry;
        $this->parser   = $parser;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $message, callable $next)
    {
        if ($message->method !== 'textDocument/didOpen') {
            return $next->__invoke($message);
        }

        $textDocument = new TextDocument(
            $message->params['textDocument']['uri'],
            $message->params['textDocument']['text'],
            $message->params['textDocument']['version']
        );

        $this->parser->parse($textDocument);

        $this->registry->add($textDocument);
    }
}
