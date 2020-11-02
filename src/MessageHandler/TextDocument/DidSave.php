<?php

declare(strict_types=1);

namespace LanguageServer\MessageHandler\TextDocument;

use LanguageServer\ParsedDocument;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\NotificationMessage;
use LanguageServer\TextDocumentRegistry;

use function assert;
use function file_get_contents;
use function is_array;
use LanguageServer\Parser\DocumentParser;

class DidSave implements MessageHandler
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
        if ($message->method !== 'textDocument/didSave') {
            return $next($message);
        }

        assert($message instanceof NotificationMessage);
        assert(is_array($message->params));

        $uri    = $message->params['textDocument']['uri'];
        $source = $this->read($uri);
        $document  = $this->parser->parse($uri, $source);

        $this->registry->add($document);
    }

    private function read(string $uri): string
    {
        return file_get_contents($uri) ?: '';
    }
}
