<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServer\Parser\ParsedDocument;
use LanguageServer\TypeResolver;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class InstanceMethodProvider implements CompletionProviderInterface
{
    private $resolver;
    private $reflector;

    public function __construct(TypeResolver $resolver, Reflector $reflector)
    {
        $this->resolver = $resolver;
        $this->reflector = $reflector;
    }

    public function complete(ParsedDocument $document, Expr $expression): array
    {
        if (!$expression->var instanceof MethodCall) {
            $expression = $expression->var;
        }

        $type = $this->resolver->getType($document, $expression);
        $reflection = $this->reflector->reflect($type);

        return $this->mapCompletionItems($document, $reflection);
    }

    private function mapCompletionItems(ParsedDocument $document, ReflectionClass $reflection): array
    {
        return array_values(array_map(
            function (ReflectionMethod $method) {
                return new CompletionItem(
                    $method->getName(),
                    CompletionItemKind::METHOD
                );
            },
            $reflection->getMethods()
        ));
    }

    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof PropertyFetch;
    }
}
