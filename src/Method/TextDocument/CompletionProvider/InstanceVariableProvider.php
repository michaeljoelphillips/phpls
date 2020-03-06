<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function array_map;
use function array_values;
use function implode;

class InstanceVariableProvider implements CompletionProvider
{
    /**
     * {@inheritdoc}
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection) : array
    {
        return array_values(array_map(
            static function (ReflectionProperty $parameter) {
                return new CompletionItem(
                    $parameter->getName(),
                    CompletionItemKind::PROPERTY,
                    implode('|', $parameter->getDocblockTypeStrings()),
                    $parameter->getDocComment()
                );
            },
            $reflection->getProperties()
        ));
    }

    public function supports(NodeAbstract $expression) : bool
    {
        return $expression instanceof PropertyFetch && ! $expression->var instanceof MethodCall;
    }
}
