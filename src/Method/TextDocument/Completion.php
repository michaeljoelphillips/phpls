<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\CursorPosition;
use LanguageServer\Parser\DocumentParserInterface;
use LanguageServer\Parser\ParsedDocument;
use LanguageServer\TextDocumentRegistry;
use LanguageServer\TypeResolver;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionList;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\NodeAbstract;
use React\Promise\Deferred;
use React\Promise\Promise;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Completion
{
    private $reflector;
    private $parser;
    private $resolver;
    private $registry;

    public function __construct(Reflector $reflector, DocumentParserInterface $parser, TypeResolver $resolver, TextDocumentRegistry $registry)
    {
        $this->reflector = $reflector;
        $this->parser = $parser;
        $this->resolver = $resolver;
        $this->registry = $registry;
    }

    public function __invoke(array $params): Promise
    {
        $completionList = $this->getCompletionList($params);

        $deferred = new Deferred();
        $deferred->resolve($completionList);

        return $deferred->promise();
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

        if ($expression->var instanceof MethodCall) {
            $type = $this->resolver->getType($parsedDocument, $expression);
        } else {
            $type = $this->resolver->getType($parsedDocument, $expression->var);
        }

        if (null === $type) {
            return $this->emptyCompletionList();
        }

        $reflection = $this->reflector->reflect($type);

        $contextualVisibility = null;

        if ($type !== $parsedDocument->getClassName()) {
            $contextualVisibility = \ReflectionMethod::IS_PUBLIC;
        }

        $methods = array_map(
            function (ReflectionMethod $method) {
                return new CompletionItem($method->getName(), 2);
            },
            $reflection->getMethods($contextualVisibility),
        );

        $properties = array_map(
            function (ReflectionProperty $parameter) {
                return new CompletionItem($parameter->getName(), 10);
            },
            $reflection->getProperties($contextualVisibility)
        );

        return new CompletionList(array_values(array_merge($properties, $methods)));
    }

    private function findExpressionAtCursor(ParsedDocument $document, CursorPosition $cursor): ?Expr
    {
        $surroundingNodes = $document->getNodesAtCursor($cursor);
        $completableNodes = array_values(array_filter($surroundingNodes, [$this, 'completable']));

        return $completableNodes[0] ?? null;
    }

    private function completable(NodeAbstract $node): bool
    {
        return $node instanceof PropertyFetch;
    }

    private function emptyCompletionList(): CompletionList
    {
        return new CompletionList();
    }
}
