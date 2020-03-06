<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use LanguageServerProtocol\InsertTextFormat;
use PhpParser\NodeAbstract;
use Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use function array_filter;
use function array_map;
use function array_values;
use function implode;
use function sprintf;

abstract class MethodProvider implements CompletionProvider
{
    /**
     * {@inheritdoc}
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection) : array
    {
        $instanceMethods = array_filter(
            $reflection->getMethods(),
            function (ReflectionMethod $method) {
                return $method->getName() !== '__construct'
                    && $this->filterMethod($method);
            }
        );

        return array_values(array_map(fn(ReflectionMethod $method) => $this->buildCompletionItem($method), $instanceMethods));
    }

    abstract protected function filterMethod(ReflectionMethod $method) : bool;

    private function buildCompletionItem(ReflectionMethod $method) : CompletionItem
    {
        $signatureInfo = $this->getSignatureInfo($method);

        return new CompletionItem($method->getName(), CompletionItemKind::METHOD, $signatureInfo, $method->getDocComment(), null, null, $method->getName(), null, null, null, null, InsertTextFormat::PLAIN_TEXT);
    }

    private function getSignatureInfo(ReflectionMethod $method) : string
    {
        $modifiers = implode(' ', Reflection::getModifierNames($method->getModifiers()));

        return sprintf('%s %s(%s): %s', $modifiers, $method->getName(), $this->getParameterInfoString($method), $this->getReturnTypeString($method));
    }

    private function getReturnTypeString(ReflectionMethod $method) : string
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

    private function getParameterInfoString(ReflectionMethod $method) : string
    {
        return implode(', ', array_map(
            static function (ReflectionParameter $parameter) {
                if ($parameter->hasType()) {
                    return sprintf('%s $%s', $parameter->getType(), $parameter->getName());
                }

                return sprintf('$%s', $parameter->getName());
            },
            $method->getParameters()
        ));
    }
}
