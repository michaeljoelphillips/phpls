<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use LanguageServer\CursorPosition;
use LanguageServer\TextDocument;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;
use function array_filter;
use function sprintf;

class ParsedDocument
{
    /** @var NodeAbstract[] */
    private array $nodes;
    private TextDocument $document;
    private NodeFinder $finder;

    /**
     * @param NodeAbstract[] $nodes
     */
    public function __construct(array $nodes, TextDocument $document)
    {
        $this->nodes    = $nodes;
        $this->document = $document;

        $this->finder = new NodeFinder();
    }

    /**
     * @return NodeAbstract[]
     */
    public function getNodesAtCursor(CursorPosition $cursorPosition) : array
    {
        return $this->searchNodes(
            static function (NodeAbstract $node) use ($cursorPosition) {
                return $cursorPosition->isWithin($node);
            }
        );
    }

    /**
     * @return NodeAbstract[]
     */
    public function getNodesBesideCursor(CursorPosition $cursorPosition) : array
    {
        return $this->searchNodes(
            static function (NodeAbstract $node) use ($cursorPosition) {
                return $cursorPosition->isSurrounding($node);
            }
        );
    }

    public function getClassName() : string
    {
        $namespace = $this->getNamespace();
        $class     = $this->finder->findFirstInstanceOf($this->nodes, Class_::class);

        return sprintf('%s\%s', $namespace, $class->name);
    }

    public function getMethod(string $methodName) : ?ClassMethod
    {
        return $this->finder->findFirst($this->nodes, static function (NodeAbstract $node) use ($methodName) {
            return $node instanceof ClassMethod
                && $node->name->name === $methodName;
        });
    }

    public function getClassProperty(string $propertyName) : ?Property
    {
        return $this->finder->findFirst($this->nodes, static function (NodeAbstract $node) use ($propertyName) {
            return $node instanceof Property
                && array_filter($node->props, static function (NodeAbstract $node) use ($propertyName) {
                    return $node->name->name === $propertyName;
                });
        });
    }

    /**
     * @return NodeAbstract[]
     */
    public function findNodes(string $class) : array
    {
        return $this->finder->findInstanceOf($this->nodes, $class);
    }

    /**
     * @return NodeAbstract[]
     */
    public function searchNodes(callable $criteria) : array
    {
        return $this->finder->find($this->nodes, $criteria);
    }

    /**
     * @return NodeAbstract[]
     */
    public function getUseStatements() : array
    {
        return $this->finder->findInstanceOf($this->nodes, Use_::class);
    }

    public function getConstructorNode() : ?ClassMethod
    {
        return $this->finder->findFirst(
            $this->nodes,
            static function (NodeAbstract $node) {
                return $node instanceof ClassMethod
                    && $node->name->name === '__construct';
            }
        );
    }

    public function getNamespace() : string
    {
        return (string) $this->finder->findFirstInstanceOf($this->nodes, Namespace_::class)->name;
    }

    /**
     * @return NodeAbstract[]
     */
    public function getNodes() : array
    {
        return $this->nodes;
    }

    public function getSource() : string
    {
        return $this->document->getSource();
    }

    public function getCursorPosition(int $line, int $character) : CursorPosition
    {
        return $this->document->getCursorPosition($line, $character);
    }
}
