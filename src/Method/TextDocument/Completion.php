<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\CursorPosition;
use LanguageServer\Method\TextDocument\CompletionProvider\CompletionProviderInterface;
use LanguageServer\Parser\DocumentParserInterface;
use LanguageServer\Parser\ParsedDocument;
use LanguageServer\TextDocumentRegistry;
use LanguageServerProtocol\CompletionList;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use React\Promise\Deferred;
use React\Promise\Promise;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Completion
{
    private $parser;
    private $registry;
    private $providers;

    public function __construct(DocumentParserInterface $parser, TextDocumentRegistry $registry, CompletionProviderInterface ...$providers)
    {
        $this->parser = $parser;
        $this->registry = $registry;
        $this->providers = $providers;
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

        return $this->completeExpression($parsedDocument, $expression);
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

    private function completeExpression(ParsedDocument $document, Expr $expression): CompletionList
    {
        $completionItems = [];
        foreach ($this->providers as $provider) {
            if ($provider->supports($expression)) {
                $completionItems = array_merge(
                    $completionItems,
                    $provider->complete($document, $expression)
                );
            }
        }

        return new CompletionList($completionItems);
    }
}
