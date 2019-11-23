<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Method\NotificationHandlerInterface;
use LanguageServer\Method\RequestHandlerInterface;
use LanguageServer\Parser\DocumentParserInterface;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DidChange implements NotificationHandlerInterface
{
    private $registry;
    private $parser;

    public function __construct(TextDocumentRegistry $registry, DocumentParserInterface $parser)
    {
        $this->registry = $registry;
        $this->parser = $parser;
    }

    public function __invoke(Message $request)
    {
        $document = new TextDocument(
            $request->params['textDocument']['uri'],
            $request->params['contentChanges'][0]['text'],
            $request->params['textDocument']['version']
        );

        $this->registry->add($document);
        $this->parser->parse($document);

        return null;
    }
}
