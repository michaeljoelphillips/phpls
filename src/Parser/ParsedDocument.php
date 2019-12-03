<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use LanguageServer\CursorPosition;
use LanguageServer\TextDocument;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ParsedDocument
{
    private array $nodes;
    private TextDocument $document;
    private NodeFinder $finder;

    public function __construct(array $nodes, TextDocument $document)
    {
        $this->nodes = $nodes;
        $this->document = $document;

        $this->finder = new NodeFinder();
    }

    /**
     * Return all nodes located at the given cursor position.
     *
     * @param int $line
     * @param int $character
     *
     * @return NodeAbstract[]
     */
    public function getNodesAtCursor(CursorPosition $cursorPosition): array
    {
        return $this->searchNodes(
            function (NodeAbstract $node) use ($cursorPosition) {
                return $cursorPosition->isWithin($node);
            }
        );
    }

    /**
     * Return all nodes located at the given cursor position.
     *
     * @param int $line
     * @param int $character
     *
     * @return NodeAbstract[]
     */
    public function getNodesBesideCursor(CursorPosition $cursorPosition): array
    {
        return $this->searchNodes(
            function (NodeAbstract $node) use ($cursorPosition) {
                return $cursorPosition->isSurrounding($node);
            }
        );
    }

    /**
     * Get the FQCN for the document.
     *
     * This method expects one class per document.
     */
    public function getClassName(): string
    {
        $namespace = $this->getNamespace();
        $class = $this->finder->findFirstInstanceOf($this->nodes, Class_::class);

        return sprintf('%s\%s', $namespace, $class->name);
    }

    public function getMethod(string $methodName): ?ClassMethod
    {
        return $this->finder->findFirst($this->nodes, function (NodeAbstract $node) use ($methodName) {
            return $node instanceof ClassMethod
                && $node->name->name === $methodName;
        });
    }

    public function findNodes(string $class): array
    {
        return $this->finder->findInstanceOf($this->nodes, $class);
    }

    public function searchNodes(callable $criteria): array
    {
        return $this->finder->find($this->nodes, $criteria);
    }

    public function getUseStatements(): array
    {
        return $this->finder->findInstanceOf($this->nodes, Use_::class);
    }

    public function getConstructorNode(): ?ClassMethod
    {
        return $this->finder->findFirst(
            $this->nodes,
            function (NodeAbstract $node) {
                return $node instanceof ClassMethod
                    && '__construct' === $node->name->name;
            }
        );
    }

    public function getNamespace(): string
    {
        return (string) $this->finder->findFirstInstanceOf($this->nodes, Namespace_::class)->name;
    }

    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getSource(): string
    {
        return $this->document->getSource();
    }

    public function getCursorPosition(int $line, int $character): CursorPosition
    {
        return $this->document->getCursorPosition($line, $character);
    }
}
