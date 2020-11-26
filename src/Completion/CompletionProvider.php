<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use LanguageServer\CursorPosition;
use LanguageServer\Inference\TypeResolver;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionList;
use PhpParser\Node;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;

use function array_merge;
use function assert;
use function in_array;

class CompletionProvider
{
    private const COMPLETEABLE_NODE_TYPES = [
        'Name',
        'Expr_Variable',
        'Expr_PropertyFetch',
        'Expr_ClassConstFetch',
    ];

    private Reflector $reflector;
    private TypeResolver $resolver;

    /** @var array<int, DocumentCompletor> */
    private array $documentCompletors = [];

    /** @var array<int, ReflectionCompletor> */
    private array $reflectionCompletors = [];

    /**
     * @param array<int, DocumentCompletor>   $documentCompletors
     * @param array<int, ReflectionCompletor> $reflectionCompletors
     */
    public function __construct(
        Reflector $reflector,
        TypeResolver $resolver,
        array $documentCompletors,
        array $reflectionCompletors
    ) {
        $this->reflector            = $reflector;
        $this->resolver             = $resolver;
        $this->documentCompletors   = $documentCompletors;
        $this->reflectionCompletors = $reflectionCompletors;
    }

    public function complete(ParsedDocument $document, CursorPosition $cursor): CompletionList
    {
        try {
            $node = $this->findCompleteableNode($document, $cursor);

            $completionItems = array_merge(
                $this->localCompletionItems($node, $document),
                $this->reflectionCompletionItems($node, $document)
            );

            return new CompletionList($completionItems);
        } catch (UncompletableNode $e) {
            return new CompletionList([]);
        }
    }

    /**
     * @return array<int, CompletionItem>
     */
    private function localCompletionItems(Node $node, ParsedDocument $document): array
    {
        $completionItems = [];

        foreach ($this->documentCompletors as $completor) {
            if ($completor->supports($node) === false) {
                continue;
            }

            $completionItems[] = $completor->complete($node, $document);
        }

        return array_merge(...$completionItems);
    }

    /**
     * @return array<int, CompletionItem>
     */
    private function reflectionCompletionItems(Node $node, ParsedDocument $document): array
    {
        $reflection      = null;
        $completionItems = [];

        foreach ($this->reflectionCompletors as $completor) {
            if ($completor->supports($node) === false) {
                continue;
            }

            if ($reflection === null) {
                try {
                    $reflection = $this->reflectNodeType($document, $node);
                } catch (UncompletableNode $e) {
                    break;
                }
            }

            $completionItems[] = $completor->complete($node, $reflection);
        }

        return array_merge(...$completionItems);
    }

    private function findCompleteableNode(ParsedDocument $document, CursorPosition $cursor): Node
    {
        $completableNodes = $document->searchNodesAtCursor(
            $cursor,
            static function (Node $node): bool {
                return in_array($node->getType(), self::COMPLETEABLE_NODE_TYPES);
            }
        );

        if (empty($completableNodes) === true) {
            throw new UncompletableNode();
        }

        return $completableNodes[0];
    }

    private function reflectNodeType(ParsedDocument $document, Node $node): ReflectionClass
    {
        $type = $this->resolver->getType($document, $node);

        if ($type === null) {
            throw new UncompletableNode();
        }

        try {
            $reflection = $this->reflector->reflect($type);
            assert($reflection instanceof ReflectionClass);

            return $reflection;
        } catch (IdentifierNotFound $e) {
            throw new UncompletableNode();
        }
    }
}
