<?php

declare(strict_types=1);

namespace LanguageServer\MessageHandler\TextDocument;

use LanguageServer\Inference\TypeResolver;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use LanguageServer\TextDocumentRegistry;
use LanguageServerProtocol\Location;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use Psr\Log\LoggerInterface;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;

class Definition implements MessageHandler
{
    private const METHOD_NAME = 'textDocument/definition';

    private TextDocumentRegistry $registry;
    private TypeResolver $typeResolver;
    private Reflector $reflector;
    private LoggerInterface $logger;

    public function __construct(TextDocumentRegistry $registry, TypeResolver $typeResolver, Reflector $reflector, LoggerInterface $logger)
    {
        $this->registry     = $registry;
        $this->typeResolver = $typeResolver;
        $this->reflector    = $reflector;
        $this->logger       = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $message, callable $next)
    {
        if ($message->method !== self::METHOD_NAME) {
            return $next($message);
        }

        $params     = $message->params;
        $document   = $this->registry->get($params['textDocument']['uri']);
        $cursor     = $document->getCursorPosition($params['position']['line'] + 1, $params['position']['character']);
        $type       = $this->typeResolver->getType($document, $document->getInnermostNodeAtCursor($cursor));
        $reflection = $this->reflector->reflect($type);

        $this->logger->debug('Finished type resolution');

        return new ResponseMessage($message, $this->locationFromReflectedClass($reflection));
    }

    private function locationFromReflectedClass(ReflectionClass $reflection) : Location
    {
        $range = new Range(
            new Position($reflection->getStartLine() - 1, $reflection->getStartColumn() - 1),
            new Position($reflection->getEndLine() - 1, $reflection->getEndColumn() - 1)
        );

        return new Location($reflection->getFileName(), $range);
    }
}
