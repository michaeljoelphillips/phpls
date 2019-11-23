<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\Method\NotificationHandlerInterface;
use LanguageServer\Parser\DocumentParserInterface;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\TextDocument;
use LanguageServer\TextDocumentRegistry;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class DidOpen implements NotificationHandlerInterface
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
        $textDocument = new TextDocument(
            $request->params['textDocument']['uri'],
            $request->params['textDocument']['text'],
            $request->params['textDocument']['version']
        );

        $this->parser->parse($textDocument);

        $this->registry->add($textDocument);

        return null;
    }
}
