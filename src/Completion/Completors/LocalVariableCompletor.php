<?php

declare(strict_types=1);

namespace LanguageServer\Completion\Completors;

use LanguageServer\Completion\DocumentCompletor;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeFinder;

use function array_map;
use function array_merge;
use function array_pop;
use function array_unique;
use function assert;
use function is_string;

use const SORT_REGULAR;

class LocalVariableCompletor implements DocumentCompletor
{
    private NodeFinder $finder;

    public function __construct()
    {
        $this->finder = new NodeFinder();
    }

    /**
     * @return CompletionItem[]
     */
    public function complete(Node $expression, ParsedDocument $document): array
    {
        assert($expression instanceof Variable);

        $parentFunctionNode = $this->findParentFunctionOfVariableNode($expression, $document);

        if ($parentFunctionNode === null) {
            return [];
        }

        $completableVariables = array_unique(array_map(
            static function (Variable $variable): CompletionItem {
                assert(is_string($variable->name));

                return new CompletionItem($variable->name, CompletionItemKind::VARIABLE, '');
            },
            $this->findCompletableVariablesWithinFunction($parentFunctionNode, $expression)
        ), SORT_REGULAR);

        if ($parentFunctionNode instanceof Closure && $parentFunctionNode->static === false) {
            $completableVariables[] = new CompletionItem('this', CompletionItemKind::VARIABLE, '');
        }

        return $completableVariables;
    }

    private function findParentFunctionOfVariableNode(Variable $variableNode, ParsedDocument $document): ?FunctionLike
    {
        $functionNodes = $this->finder->find($document->getNodes(), static function (Node $node) use ($variableNode): bool {
            return $node instanceof FunctionLike
                && $node->getEndFilePos() >= $variableNode->getEndFilePos()
                && $node->getStartFilePos() <= $variableNode->getStartFilePos();
        });

        $parentFunction = array_pop($functionNodes);
        assert($parentFunction instanceof FunctionLike || $parentFunction === null);

        return $parentFunction;
    }

    /**
     * @return array<int, Variable>
     */
    private function findCompletableVariablesWithinFunction(FunctionLike $function, Variable $variableNode): array
    {
        $nodes = array_merge(
            $function->getParams(),
            $function->getStmts() ?? [],
            $function instanceof Closure ? $function->uses : []
        );

        /** @var array<int, Variable> $nodes */
        $nodes = $this->finder->find($nodes, static function (Node $node) use ($variableNode): bool {
            return $node instanceof Variable
                && $node->getStartFilePos() < $variableNode->getStartFilePos();
        });

        return $nodes;
    }

    public function supports(Node $expression): bool
    {
        return $expression instanceof Variable;
    }
}
