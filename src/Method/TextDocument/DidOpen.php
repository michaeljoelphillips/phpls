<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Server\MessageHandlerInterface;
use LanguageServer\Parser\DocumentParserInterface;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DidOpen implements MessageHandlerInterface
{
    private TextDocumentRegistry $registry;
    private DocumentParserInterface $parser;

    public function __construct(TextDocumentRegistry $registry, DocumentParserInterface $parser)
    {
        $this->registry = $registry;
        $this->parser = $parser;
    }

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
