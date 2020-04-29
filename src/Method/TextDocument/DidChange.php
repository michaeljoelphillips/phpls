<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\ParsedDocument;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\TextDocumentRegistry;
use PhpParser\Parser;

class DidChange implements MessageHandler
{
    private TextDocumentRegistry $registry;
    private Parser $parser;

    public function __construct(TextDocumentRegistry $registry, Parser $parser)
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

        $uri    = $message->params['textDocument']['uri'];
        $source = $message->params['contentChanges'][0]['text'];
        $nodes  = $this->parser->parse($source);

        $this->registry->add(new ParsedDocument($uri, $source, $nodes));
    }
}
