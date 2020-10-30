<?php

declare(strict_types=1);

namespace LanguageServer\MessageHandler\TextDocument;

use LanguageServer\Inference\TypeResolver;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Server\Protocol\ResponseMessage;
use LanguageServer\TextDocumentRegistry;
use LanguageServerProtocol\Location;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use PhpParser\Node\Name;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;

use function assert;
use function is_array;

class Definition implements MessageHandler
{
    private const METHOD_NAME = 'textDocument/definition';

    private TextDocumentRegistry $registry;
    private TypeResolver $typeResolver;
    private Reflector $reflector;

    public function __construct(TextDocumentRegistry $registry, TypeResolver $typeResolver, Reflector $reflector)
    {
        $this->registry     = $registry;
        $this->typeResolver = $typeResolver;
        $this->reflector    = $reflector;
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
        $cursor   = $document->getCursorPosition($params['position']['line'], $params['position']['character']);

        $node = $document->getInnermostNodeAtCursor($cursor);

        if ($node instanceof Name === false) {
            return new ResponseMessage($message, null);
        }

        try {
            $type = $this->typeResolver->getType($document, $node);

            if ($type === null) {
                return new ResponseMessage($message, null);
            }

            $reflection = $this->reflector->reflect($type);

            assert($reflection instanceof ReflectionClass);

            return new ResponseMessage($message, $this->locationFromReflectedClass($reflection));
        } catch (IdentifierNotFound $e) {
            return new ResponseMessage($message, null);
        }
    }

    private function locationFromReflectedClass(ReflectionClass $reflection): Location
    {
        $range = new Range(
            new Position($reflection->getStartLine() - 1, $reflection->getStartColumn() - 1),
            new Position($reflection->getEndLine() - 1, $reflection->getEndColumn() - 1)
        );

        return new Location($reflection->getFileName(), $range);
    }
}
