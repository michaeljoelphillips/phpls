<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use LanguageServerProtocol\InsertTextFormat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\NodeAbstract;
use Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;

class MethodProvider implements CompletionProviderInterface
{
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array
    {
        $instanceMethods = array_filter(
            $reflection->getMethods(),
            function (ReflectionMethod $method) {
                return '__construct' !== $method->getName();
            }
        );

        return array_values(array_map([$this, 'buildCompletionItem'], $instanceMethods));
    }

    private function buildCompletionItem(ReflectionMethod $method): CompletionItem
    {
        $signatureInfo = $this->getSignatureInfo($method);

        return new CompletionItem($method->getName(), CompletionItemKind::METHOD, $signatureInfo, $method->getDocComment(), null, null, $method->getName(), null, null, null, null, InsertTextFormat::PLAIN_TEXT);
    }

    private function getSignatureInfo(ReflectionMethod $method): string
    {
        $modifiers = implode(' ', Reflection::getModifierNames($method->getModifiers()));

        return sprintf('%s %s(%s): %s', $modifiers, $method->getName(), $this->getParameterInfoString($method), $this->getReturnTypeString($method));
    }

    private function getReturnTypeString(ReflectionMethod $method): string
    {
        if ($method->hasReturnType()) {
            return (string) $method->getReturnType();
        }

        $docblockReturnTypes = $method->getDocBlockReturnTypes();

        if (false === empty($docblockReturnTypes)) {
            return implode('|', $docblockReturnTypes);
        }

        return 'mixed';
    }

    private function getParameterInfoString(ReflectionMethod $method): string
    {
        return implode(', ', array_map(
            function (ReflectionParameter $parameter) {
                if ($parameter->hasType()) {
                    return sprintf('%s $%s', $parameter->getType(), $parameter->getName());
                }

                return sprintf('$%s', $parameter->getName());
            },
            $method->getParameters()
        ));
    }

    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof PropertyFetch
            || $expression instanceof ClassConstFetch;
    }
}
