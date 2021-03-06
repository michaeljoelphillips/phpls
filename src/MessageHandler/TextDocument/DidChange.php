<?php

declare(strict_types=1);

namespace LanguageServer\MessageHandler\TextDocument;

use LanguageServer\Parser\DocumentParser;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\NotificationMessage;
use LanguageServer\TextDocumentRegistry;

use function assert;
use function is_array;

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
            return $next($message);
        }

        assert($message instanceof NotificationMessage);
        assert(is_array($message->params));

        $uri      = $message->params['textDocument']['uri'];
        $source   = $message->params['contentChanges'][0]['text'];
        $document = $this->parser->parse($uri, $source);

        $this->registry->add($document);
    }
}
