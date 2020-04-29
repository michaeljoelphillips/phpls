<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Parser\ParsedDocument;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\TextDocumentRegistry;
use PhpParser\Parser;

class DidOpen implements MessageHandler
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
        if ($message->method !== 'textDocument/didOpen') {
            return $next->__invoke($message);
        }

        $uri    = $message->params['textDocument']['uri'];
        $source = $message->params['textDocument']['text'];
        $nodes  = $this->parser->parse($source);

        $this->registry->add(new ParsedDocument($uri, $source, $nodes));
    }
}
