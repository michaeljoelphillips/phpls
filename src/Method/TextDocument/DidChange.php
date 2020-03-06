<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Parser\DocumentParser;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;

class DidChange implements MessageHandler
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
        if ($message->method !== 'textDocument/didChange') {
            return $next->__invoke($message);
        }

        $document = new TextDocument(
            $message->params['textDocument']['uri'],
            $message->params['contentChanges'][0]['text'],
            $message->params['textDocument']['version']
        );

        $this->registry->add($document);
        $this->parser->parse($document);
    }
}
