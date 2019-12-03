<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\CursorPosition;
use LanguageServer\Method\RequestHandlerInterface;
use LanguageServer\Method\TextDocument\CompletionProvider\CompletionProviderInterface;
use LanguageServer\Parser\DocumentParserInterface;
use LanguageServer\Parser\ParsedDocument;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use LanguageServer\TextDocumentRegistry;
use LanguageServer\TypeResolver;
use LanguageServerProtocol\CompletionList;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Completion implements RequestHandlerInterface
{
    private DocumentParserInterface $parser;
    private TextDocumentRegistry $registry;
    private Reflector $reflector;
    private TypeResolver $resolver;
    private array $providers;

    public function __construct(DocumentParserInterface $parser, TextDocumentRegistry $registry, Reflector $reflector, TypeResolver $resolver, CompletionProviderInterface ...$providers)
    {
        $this->parser = $parser;
        $this->registry = $registry;
        $this->reflector = $reflector;
        $this->resolver = $resolver;
        $this->providers = $providers;
    }

    public function __invoke(Message $request)
    {
        return new ResponseMessage($request, $this->getCompletionList($request->params));
    }

    private function getCompletionList(array $params): CompletionList
    {
        $document = $this->registry->get($params['textDocument']['uri']);
        $parsedDocument = $this->parser->parse($document);

        $cursorPosition = $document->getCursorPosition(
            $params['position']['line'] + 1,
            $params['position']['character']
        );

        $expression = $this->findExpressionAtCursor($parsedDocument, $cursorPosition);

        if (null === $expression) {
            return $this->emptyCompletionList();
        }

        $type = $this->resolver->getType($parsedDocument, $expression);

        if (null === $type) {
            return $this->emptyCompletionList();
        }

        $reflection = $this->reflector->reflect($type);

        return $this->completeExpression($expression, $reflection);
    }

    private function findExpressionAtCursor(ParsedDocument $document, CursorPosition $cursor): ?Expr
    {
        $surroundingNodes = $document->getNodesAtCursor($cursor);
        $completableNodes = array_values(array_filter($surroundingNodes, [$this, 'completable']));

        return $completableNodes[0] ?? null;
    }

    private function completable(NodeAbstract $node): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($node)) {
                return true;
            }
        }

        return false;
    }

    private function emptyCompletionList(): CompletionList
    {
        return new CompletionList();
    }

    private function completeExpression(Expr $expression, ReflectionClass $reflection): CompletionList
    {
        $completionItems = [];
        foreach ($this->providers as $provider) {
            if ($provider->supports($expression)) {
                $completionItems = array_merge(
                    $completionItems,
                    $provider->complete($expression, $reflection)
                );
            }
        }

        return new CompletionList($completionItems);
    }
}
