<?php

declare(strict_types=1);

namespace LanguageServer\Completion\Completors;

use LanguageServer\Completion\DocumentCompletor;
use LanguageServer\Inference\TypeResolver;
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
use function assert;
use function count;
use function is_string;

class LocalVariableCompletor implements DocumentCompletor
{
    private TypeResolver $typeResolver;

    private NodeFinder $finder;

    public function __construct(TypeResolver $typeResolver)
    {
        $this->typeResolver = $typeResolver;
        $this->finder       = new NodeFinder();
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

        return array_map(
            function (Variable $variable) use ($document): CompletionItem {
                assert(is_string($variable->name));

                return new CompletionItem($variable->name, CompletionItemKind::VARIABLE, $this->typeResolver->getType($document, $variable) ?? '');
            },
            $this->findCompletableVariablesWithinFunction($parentFunctionNode, $expression)
        );
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

        if ($function instanceof Closure && $function->static === false) {
            $nodes[] = new Variable('this');
        }

        /** @var array<int, Variable> $nodes */
        $nodes = $this->finder->find($nodes, static function (Node $node) use ($variableNode): bool {
            return $node instanceof Variable
                && $node->getStartFilePos() < $variableNode->getStartFilePos();
        });

        return $this->uniqueVariableNodes($nodes);
    }

    /**
     * @param array<int, Variable> $nodes
     *
     * @return array<int, Variable>
     */
    private function uniqueVariableNodes(array $nodes): array
    {
        $uniqueNodes = [];

        for ($i = 0; $i < count($nodes); $i++) {
            $isUnique = true;

            for ($j = $i + 1; $j < count($nodes); $j++) {
                if ($nodes[$i]->name !== $nodes[$j]->name) {
                    continue;
                }

                $isUnique = false;

                break;
            }

            if ($isUnique !== true) {
                continue;
            }

            $uniqueNodes[] = $nodes[$i];
        }

        return $uniqueNodes;
    }

    public function supports(Node $expression): bool
    {
        return $expression instanceof Variable;
    }
}
