<?php

declare(strict_types=1);

namespace LanguageServer\LSP;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ParsedDocument
{
    /** @var NodeAbstract[] */
    private $nodes;

    /** @var TextDocument */
    private $document;

    /** @var NodeFinder */
    private $finder;

    public function __construct(array $nodes, TextDocument $document)
    {
        $this->nodes = $nodes;
        $this->document = $document;

        $this->finder = new NodeFinder();
    }

    /**
     * Return the MethodCall under the given cursor.
     *
     * @param int $line
     * @param int $character
     *
     * @return MethodCall
     */
    public function getMethodAtCursor(int $line, int $character): MethodCall
    {
        $cursorPosition = $this->document->getCursorPosition($line, $character);

        $methodCall = $this->finder->findFirst($this->nodes, function (NodeAbstract $node) use ($line, $cursorPosition) {
            return $line === $node->getLine()
                && $node instanceof MethodCall
                && $node->getStartFilePos() <= $cursorPosition
                && $node->getEndFilePos() >= $cursorPosition;
        });

        return $methodCall;
    }

    /**
     * Get the FQCN for the document.
     *
     * This method expects one class per document.
     *
     * @return string
     */
    public function getClassName(): string
    {
        $namespace = $this->finder->findFirstInstanceOf($this->nodes, Namespace_::class);
        $class = $this->finder->findFirstInstanceOf($this->nodes, Class_::class);

        return sprintf('%s\%s', $namespace->name, $class->name);
    }

    /**
     * Get a method node by name.
     *
     * @param string $methodName
     *
     * @return ClassMethod
     */
    public function getMethod(string $methodName): ClassMethod
    {
        return $this->finder->findFirst($this->nodes, function (NodeAbstract $node) use ($methodName) {
            return $node instanceof ClassMethod
                && $node->name->name === $methodName;
        });
    }

    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getSource(): string
    {
        return $this->document->getSource();
    }
}
