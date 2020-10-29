<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;
use Roave\BetterReflection\Reflection\ReflectionClass;

use function array_map;
use function array_merge;
use function array_pop;
use function array_unique;
use function assert;

use const SORT_REGULAR;

class LocalVariableProvider implements CompletionProvider
{
    private NodeFinder $finder;

    public function __construct()
    {
        $this->finder = new NodeFinder();
    }

    /**
     * @return CompletionItem[]
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array
    {
        assert($expression instanceof Variable);

        $parentFunctionNode = $this->findParentFunctionOfVariableNode($expression, $reflection->getAst()->stmts);

        if ($parentFunctionNode === null) {
            return [];
        }

        $completableVariables = array_unique(array_map(
            static function (Variable $variable): CompletionItem {
                return new CompletionItem(
                    (string) $variable->name,
                    CompletionItemKind::VARIABLE,
                    '',
                );
            },
            $this->findCompletableVariablesWithinFunction($parentFunctionNode, $expression)
        ), SORT_REGULAR);

        if ($parentFunctionNode instanceof Closure && $parentFunctionNode->static === false) {
            $completableVariables[] = new CompletionItem('this', CompletionItemKind::VARIABLE, '');
        }

        return $completableVariables;
    }

    /**
     * @param array<int, NodeAbstract> $classAst
     */
    private function findParentFunctionOfVariableNode(Variable $variableNode, array $classAst): ?FunctionLike
    {
        $functionNodes = $this->finder->find($classAst, static function (NodeAbstract $node) use ($variableNode): bool {
            return $node instanceof FunctionLike
                && $node->getEndFilePos() >= $variableNode->getEndFilePos()
                && $node->getStartFilePos() <= $variableNode->getStartFilePos();
        });

        return array_pop($functionNodes);
    }

    /**
     * @return array<int, Variable>
     */
    private function findCompletableVariablesWithinFunction(FunctionLike $function, Variable $variableNode): array
    {
        $nodes = array_merge(
            $function->getParams(),
            $function->getStmts(),
            $function instanceof Closure ? $function->uses : []
        );

        return $this->finder->find($nodes, static function (NodeAbstract $node) use ($variableNode): bool {
            return $node instanceof Variable
                && $node->getStartFilePos() < $variableNode->getStartFilePos();
        });
    }

    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof Variable;
    }
}
