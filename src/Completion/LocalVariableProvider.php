<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function array_map;
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
    public function complete(NodeAbstract $expression, ReflectionClass $reflection) : array
    {
        assert($expression instanceof Variable);

        $parentFunctionNode = $this->findParentFunctionOfVariableNode($expression, $reflection->getAst()->stmts);

        if ($parentFunctionNode === null) {
            return [];
        }

        return array_unique(array_map(
            static function (Variable $variable) : CompletionItem {
                return new CompletionItem(
                    (string) $variable->name,
                    CompletionItemKind::VARIABLE,
                    ''
                );
            },
            $this->findCompletableVariablesWithinFunction($parentFunctionNode, $expression)
        ), SORT_REGULAR);
    }

    /**
     * @param array<int, NodeAbstract> $classAst
     */
    private function findParentFunctionOfVariableNode(Variable $variableNode, array $classAst) : ?FunctionLike
    {
        return $this->finder->findFirst($classAst, static function (NodeAbstract $node) use ($variableNode) : bool {
            return $node instanceof FunctionLike
                && $node->getEndFilePos() >= $variableNode->getEndFilePos()
                && $node->getStartFilePos() <= $variableNode->getStartFilePos();
        });
    }

    /**
     * @return array<int, Variable>
     */
    private function findCompletableVariablesWithinFunction(FunctionLike $function, Variable $variableNode) : array
    {
        return $this->finder->find($function->getStmts(), static function (NodeAbstract $node) use ($variableNode) : bool {
            return $node instanceof Variable
                && $node->getStartFilePos() < $variableNode->getStartFilePos();
        });
    }

    public function supports(NodeAbstract $expression) : bool
    {
        return $expression instanceof Variable;
    }
}
