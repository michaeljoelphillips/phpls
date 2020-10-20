<?php

declare(strict_types=1);

namespace LanguageServer\MessageHandler\TextDocument;

use LanguageServer\Completion\CompletionProvider;
use LanguageServer\CursorPosition;
use LanguageServer\Inference\TypeResolver;
use LanguageServer\ParsedDocument;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use LanguageServer\TextDocumentRegistry;
use LanguageServerProtocol\CompletionList;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\NodeAbstract;
use Psr\Log\LoggerInterface;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;
use function array_filter;
use function array_merge;
use function array_values;
use function count;
use function sprintf;

class Completion implements MessageHandler
{
    private const METHOD_NAME = 'textDocument/completion';

    private TextDocumentRegistry $registry;
    private Reflector $reflector;
    private TypeResolver $resolver;
    private LoggerInterface $logger;

    /** @var CompletionProvider[] */
    private array $providers;

    public function __construct(TextDocumentRegistry $registry, Reflector $reflector, TypeResolver $resolver, LoggerInterface $logger, CompletionProvider ...$providers)
    {
        $this->registry  = $registry;
        $this->reflector = $reflector;
        $this->resolver  = $resolver;
        $this->providers = $providers;
        $this->logger    = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $message, callable $next)
    {
        if ($message->method !== self::METHOD_NAME) {
            return $next->__invoke($message);
        }

        return new ResponseMessage($message, $this->getCompletionList($message->params));
    }

    /**
     * @param array<string, mixed> $params
     */
    private function getCompletionList(array $params) : CompletionList
    {
        $parsedDocument = $this->registry->get($params['textDocument']['uri']);

        $cursorPosition = $parsedDocument->getCursorPosition(
            $params['position']['line'],
            $params['position']['character'] - 1
        );

        $expression = $this->findNodeAtCursor($parsedDocument, $cursorPosition);

        if ($expression === null) {
            $this->logger->notice('A completable expression was not found');

            return $this->emptyCompletionList();
        }

        if ($expression instanceof Variable || $expression instanceof Name) {
            $type = $this->resolver->getType($parsedDocument, new PropertyFetch(new Variable('this'), 'foo'));
        } else {
            $type = $this->resolver->getType($parsedDocument, $expression);
        }

        if ($type === null) {
            $this->logger->error('The type could not be inferred from the expression', ['expression' => $expression]);

            return $this->emptyCompletionList();
        }

        $reflection = $this->reflector->reflect($type);

        return $this->completeExpression($expression, $reflection);
    }

    private function findNodeAtCursor(ParsedDocument $document, CursorPosition $cursor) : ?NodeAbstract
    {
        $surroundingNodes = $document->getNodesAtCursor($cursor);
        $completableNodes = array_values(array_filter($surroundingNodes, fn(NodeAbstract $node) => $this->completable($node)));

        return $completableNodes[0] ?? null;
    }

    private function completable(NodeAbstract $node) : bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($node)) {
                return true;
            }
        }

        return false;
    }

    private function emptyCompletionList() : CompletionList
    {
        return new CompletionList();
    }

    private function completeExpression(NodeAbstract $expression, ReflectionClass $reflection) : CompletionList
    {
        $completionItems = [];
        foreach ($this->providers as $provider) {
            if (! $provider->supports($expression)) {
                continue;
            }

            $completionItems = array_merge(
                $completionItems,
                $provider->complete($expression, $reflection)
            );
        }

        $this->logger->debug(sprintf('Completed %d items', count($completionItems)));

        return new CompletionList($completionItems);
    }
}
