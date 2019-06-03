<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\CursorPosition;
use LanguageServer\Method\TextDocument\CompletionProvider\CompletionProviderInterface;
use LanguageServer\Parser\DocumentParserInterface;
use LanguageServer\Parser\ParsedDocument;
use LanguageServer\TextDocumentRegistry;
use LanguageServer\TypeResolver;
use LanguageServerProtocol\CompletionList;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Completion
{
    private $parser;
    private $registry;
    private $reflector;
    private $resolver;
    private $providers;

    public function __construct(DocumentParserInterface $parser, TextDocumentRegistry $registry, Reflector $reflector, TypeResolver $resolver, CompletionProviderInterface ...$providers)
    {
        $this->parser = $parser;
        $this->registry = $registry;
        $this->reflector = $reflector;
        $this->resolver = $resolver;
        $this->providers = $providers;
    }

    public function __invoke(array $params)
    {
        return $this->getCompletionList($params);
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

        return $this->completeExpression($reflection, $expression);
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
