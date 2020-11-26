<?php

declare(strict_types=1);

namespace LanguageServer\MessageHandler\TextDocument;

use LanguageServer\Completion\CompletionProvider;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Server\Protocol\ResponseMessage;
use LanguageServer\TextDocumentRegistry;

use function assert;
use function is_array;

class Completion implements MessageHandler
{
    private const METHOD_NAME = 'textDocument/completion';

    private TextDocumentRegistry $registry;
    private CompletionProvider $provider;

    public function __construct(TextDocumentRegistry $registry, CompletionProvider $provider)
    {
        $this->registry = $registry;
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $message, callable $next)
    {
        if ($message->method !== self::METHOD_NAME) {
            return $next($message);
        }

        assert($message instanceof RequestMessage);
        assert(is_array($message->params));

        $params   = $message->params;
        $document = $this->registry->get($params['textDocument']['uri']);
        $cursor   = $document->getCursorPosition($params['position']['line'], $params['position']['character'] - 1);

        return new ResponseMessage($message, $this->provider->complete($document, $cursor));
    }
}
