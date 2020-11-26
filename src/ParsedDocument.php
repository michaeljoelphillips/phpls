<?php

declare(strict_types=1);

namespace LanguageServer;

use OutOfBoundsException;
use PhpParser\Error;
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
use function array_key_exists;
use function array_key_last;
use function assert;
use function explode;
use function ltrim;
use function parse_url;
use function sprintf;
use function str_split;
use function strlen;

use const PHP_EOL;

class ParsedDocument
{
    private string $uri;

    private string $source;

    /** @var NodeAbstract[] */
    private array $nodes;

    /** @var Error[] */
    private array $errors;

    private bool $persisted = false;

    private NodeFinder $finder;

    /**
     * @param NodeAbstract[] $nodes
     * @param Error[]        $errors
     */
    public function __construct(string $uri, string $source, array $nodes, array $errors = [], bool $persisted = false)
    {
        $this->uri       = $uri;
        $this->source    = $source;
        $this->nodes     = $nodes;
        $this->errors    = $errors;
        $this->persisted = $persisted;

        $this->finder = new NodeFinder();
    }

    public function isPersisted(): bool
    {
        return $this->persisted;
    }

    public function markAsPersisted(): void
    {
        $this->persisted = true;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        $url = parse_url($this->getUri());

        assert($url !== false);
        assert(array_key_exists('path', $url));

        return $url['path'];
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

    public function hasErrors(): bool
    {
        return empty($this->getErrors()) === false;
    }

    /**
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errors;
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

    /**
     * @return Node[]
     */
    public function searchNodesAtCursor(CursorPosition $cursorPosition, callable $predicate): array
    {
        return $this->searchNodes(
            static function (NodeAbstract $node) use ($cursorPosition, $predicate) {
                return $cursorPosition->isWithin($node) && $predicate($node);
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
     * @return array<int, int>
     */
    public function getColumnPositions(int $line): array
    {
        $lines = explode(PHP_EOL, $this->source);

        if (isset($lines[$line]) === false) {
            throw new OutOfBoundsException(sprintf('Line %d does not exist within the document', $line));
        }

        $line  = $lines[$line];
        $end   = strlen($line);
        $start = $end - strlen(ltrim($line));

        return [$start, $end];
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
