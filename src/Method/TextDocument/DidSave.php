<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;
use function file_get_contents;

class DidSave implements MessageHandler
{
    private TextDocumentRegistry $registry;

    public function __construct(TextDocumentRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $message, callable $next)
    {
        if ($message->method !== 'textDocument/didSave') {
            return $next->__invoke($message);
        }

        $uri      = $message->params['textDocument']['uri'];
        $document = new TextDocument($uri, $this->read($uri), 0);

        $this->registry->add($document);
    }

    private function read(string $uri) : string
    {
        return file_get_contents($uri) ?: '';
    }
}
