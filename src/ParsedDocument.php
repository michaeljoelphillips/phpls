<?php

declare(strict_types=1);

namespace LanguageServer;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;

use function array_filter;
use function array_key_last;
use function sprintf;
use function str_split;

use const PHP_EOL;

class ParsedDocument
{
    /** @var NodeAbstract[] */
    private array $nodes;
    private string $uri;
    private string $source;
    private NodeFinder $finder;

    /**
     * @param NodeAbstract[] $nodes
     */
    public function __construct(string $uri, string $source, array $nodes)
    {
        $this->uri    = $uri;
        $this->source = $source;
        $this->nodes  = $nodes;

        $this->finder = new NodeFinder();
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return NodeAbstract[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getInnermostNodeAtCursor(CursorPosition $cursorPosition): ?NodeAbstract
    {
        $nodes = $this->getNodesAtCursor($cursorPosition);

        if (empty($nodes)) {
            return null;
        }

        return $nodes[array_key_last($nodes)];
    }

    /**
     * @return NodeAbstract[]
     */
    public function getNodesAtCursor(CursorPosition $cursorPosition): array
    {
        return $this->searchNodes(
            static function (NodeAbstract $node) use ($cursorPosition) {
                return $cursorPosition->isWithin($node);
            }
        );
    }

    public function getClassName(): string
    {
        $namespace = $this->getNamespace();
        $class     = $this->finder->findFirstInstanceOf($this->getNodes(), Class_::class);

        return sprintf('%s\%s', $namespace, $class->name);
    }

    public function getMethod(string $methodName): ?ClassMethod
    {
        return $this->finder->findFirst($this->getNodes(), static function (NodeAbstract $node) use ($methodName) {
            return $node instanceof ClassMethod
                && $node->name->name === $methodName;
        });
    }

    public function getClassProperty(string $propertyName): ?Property
    {
        return $this->finder->findFirst($this->getNodes(), static function (NodeAbstract $node) use ($propertyName) {
            return $node instanceof Property
                && array_filter($node->props, static function (NodeAbstract $node) use ($propertyName) {
                    return $node->name->name === $propertyName;
                });
        });
    }

    /**
     * @return NodeAbstract[]
     */
    public function findNodes(string $class): array
    {
        return $this->finder->findInstanceOf($this->getNodes(), $class);
    }

    /**
     * @return NodeAbstract[]
     */
    public function searchNodes(callable $criteria): array
    {
        return $this->finder->find($this->getNodes(), $criteria);
    }

    /**
     * @return NodeAbstract[]
     */
    public function getUseStatements(): array
    {
        return $this->finder->findInstanceOf($this->getNodes(), Use_::class);
    }

    public function getConstructorNode(): ?ClassMethod
    {
        return $this->finder->findFirst(
            $this->getNodes(),
            static function (NodeAbstract $node) {
                return $node instanceof ClassMethod
                    && $node->name->name === '__construct';
            }
        );
    }

    public function getNamespace(): string
    {
        return (string) $this->finder->findFirstInstanceOf($this->getNodes(), Namespace_::class)->name;
    }

    /**
     * Calculate the cursor position relative to the beginning of the file,
     * beginning at 1.
     */
    public function getCursorPosition(int $lineNumber, int $characterOffset): CursorPosition
    {
        $linePosition      = 0;
        $characterPosition = 0;

        foreach (str_split($this->source) as $relativePosition => $character) {
            if ($character === PHP_EOL) {
                $linePosition++;
                $characterPosition = 0;

                continue;
            }

            if ($linePosition === $lineNumber && $characterPosition === $characterOffset) {
                return new CursorPosition($lineNumber, $characterPosition, $relativePosition);
            }

            $characterPosition++;
        }

        return new CursorPosition($lineNumber, $characterPosition, -1);
    }
}
