<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use LanguageServerProtocol\InsertTextFormat;
use Reflection;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class InstanceMethodProvider implements CompletionProviderInterface
{
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array
    {
        $instanceMethods = array_filter(
            $reflection->getMethods(),
            function (ReflectionMethod $method) {
                return $method->getName() !== '__construct';
            }
        );

        return array_values(array_map([$this, 'buildCompletionItem'], $instanceMethods));
    }

    private function buildCompletionItem(ReflectionMethod $method): CompletionItem
    {
        $signatureInfo = $this->getSignatureInfo($method);

        return new CompletionItem($signatureInfo, CompletionItemKind::METHOD, $signatureInfo, $method->getDocComment(), null, null, $method->getName(), null, null, null, null, InsertTextFormat::PLAIN_TEXT);
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

        if (empty($docblockReturnTypes) === false) {
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

    private function prepareReturnTypeInfo(ReflectionMethod $method): string
    {
    }

    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof PropertyFetch;
    }
}
