<?php

declare(strict_types=1);

namespace LanguageServer;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;

use function array_filter;
use function array_key_last;
use function assert;
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

    public function getInnermostNodeAtCursor(CursorPosition $cursorPosition): ?Node
    {
        $nodes = $this->getNodesAtCursor($cursorPosition);

        if (empty($nodes)) {
            return null;
        }

        return $nodes[array_key_last($nodes)];
    }

    /**
     * @return Node[]
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

        assert($class instanceof Class_);

        return sprintf('%s\%s', $namespace, $class->name);
    }

    public function getMethod(string $methodName): ?ClassMethod
    {
        $classMethod = $this->finder->findFirst($this->getNodes(), static function (NodeAbstract $node) use ($methodName) {
            return $node instanceof ClassMethod
                && $node->name->name === $methodName;
        });

        assert($classMethod instanceof ClassMethod || $classMethod === null);

        return $classMethod;
    }

    public function getClassProperty(string $propertyName): ?Property
    {
        $property = $this->finder->findFirst($this->getNodes(), static function (NodeAbstract $node) use ($propertyName) {
            return $node instanceof Property
                && array_filter($node->props, static function (PropertyProperty $node) use ($propertyName) {
                    return $node->name->name === $propertyName;
                });
        });

        assert($property instanceof Property || $property === null);

        return $property;
    }

    /**
     * @return Node[]
     */
    public function findNodes(string $class): array
    {
        return $this->finder->findInstanceOf($this->getNodes(), $class);
    }

    /**
     * @return Node[]
     */
    public function searchNodes(callable $criteria): array
    {
        return $this->finder->find($this->getNodes(), $criteria);
    }

    /**
     * @return Use_[]
     */
    public function getUseStatements(): array
    {
        /** @var array<int, Use_> $useStatements */
        $useStatements = $this->finder->findInstanceOf($this->getNodes(), Use_::class);

        return $useStatements;
    }

    public function getConstructorNode(): ?ClassMethod
    {
        $constructor = $this->finder->findFirst(
            $this->getNodes(),
            static function (NodeAbstract $node) {
                return $node instanceof ClassMethod
                    && $node->name->name === '__construct';
            }
        );

        assert($constructor instanceof ClassMethod || $constructor === null);

        return $constructor;
    }

    public function getNamespace(): string
    {
        $namespace = $this->finder->findFirstInstanceOf($this->getNodes(), Namespace_::class);

        assert($namespace instanceof Namespace_);

        return (string) $namespace->name;
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
