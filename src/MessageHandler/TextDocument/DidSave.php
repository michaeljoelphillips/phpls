<?php

declare(strict_types=1);

namespace LanguageServer\MessageHandler\TextDocument;

use LanguageServer\ParsedDocument;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\TextDocumentRegistry;
use PhpParser\Parser;

use function assert;
use function file_get_contents;
use function is_array;

class DidSave implements MessageHandler
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
        if ($message->method !== 'textDocument/didSave') {
            return $next($message);
        }

        assert($message instanceof RequestMessage);
        assert(is_array($message->params));

        $uri    = $message->params['textDocument']['uri'];
        $source = $this->read($uri);
        $nodes  = $this->parser->parse($source);

        $this->registry->add(new ParsedDocument($uri, $source, $nodes ?? []));
    }

    private function read(string $uri): string
    {
        return file_get_contents($uri) ?: '';
    }
}
