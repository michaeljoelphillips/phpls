<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function preg_match;
use function sprintf;
use function strpos;

class MethodDocTagProvider implements CompletionProvider
{
    /**
     * {@inheritdoc}
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection) : array
    {
        $methods = $this->filterMethodsFromDocblock($reflection->getDocComment());

        return $this->mapMethodsToCompletionItems($methods);
    }

    /**
     * @return string[]
     */
    private function filterMethodsFromDocblock(string $docblock) : array
    {
        return array_filter(explode("\n", $docblock), static function (string $line) : bool {
            return strpos($line, '@method') !== false
                && strpos($line, 'static') === false;
        });
    }

    /**
     * @param string[] $methods
     *
     * @return CompletionItem[]
     */
    private function mapMethodsToCompletionItems(array $methods) : array
    {
        return array_values(array_map(
            static function (string $method) {
                $methodParts = [];
                preg_match('/\w+ (\w+)\(.*\)/', $method, $methodParts);

                return new CompletionItem(
                    $methodParts[1],
                    CompletionItemKind::METHOD,
                    sprintf('public %s', $methodParts[0]),
                );
            },
            $methods
        ));
    }

    public function supports(NodeAbstract $expression) : bool
    {
        return $expression instanceof PropertyFetch
            || $expression instanceof ClassConstFetch;
    }
}
