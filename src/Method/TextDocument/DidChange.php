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
class DidChange implements MessageHandlerInterface
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
        $document = new TextDocument(
            $message->params['textDocument']['uri'],
            $message->params['contentChanges'][0]['text'],
            $message->params['textDocument']['version']
        );

        $this->registry->add($document);
        $this->parser->parse($document);
    }
}
