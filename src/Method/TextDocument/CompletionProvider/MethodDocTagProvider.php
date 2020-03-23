<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function array_column;
use function array_map;
use function preg_match_all;
use function sprintf;

class MethodDocTagProvider implements CompletionProvider
{
    /**
     * {@inheritdoc}
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection) : array
    {
        $methods = $this->parseMethodsInDocblock($reflection->getDocComment());

        return $this->mapMethodsToCompletionItems($methods);
    }

    /**
     * @return string[]
     */
    private function parseMethodsInDocblock(string $docblock) : array
    {
        $matches         = [];
        $numberOfMatches = preg_match_all('/@method (\w+) ((\w+)\(.*\))/', $docblock, $matches);

        if ((bool) $numberOfMatches === false) {
            return [];
        }

        $parsedMehods = [];
        for ($i = 0; $i < $numberOfMatches; $i++) {
            $parsedMethods[] = array_column($matches, $i);
        }

        return $parsedMethods;
    }

    /**
     * @param string[] $methods
     *
     * @return CompletionItem[]
     */
    private function mapMethodsToCompletionItems(array $methods) : array
    {
        return array_map(
            static function (array $method) : CompletionItem {
                return new CompletionItem(
                    $method[3],
                    CompletionItemKind::METHOD,
                    sprintf('public %s: %s', $method[2], $method[1]),
                );
            },
            $methods
        );
    }

    public function supports(NodeAbstract $expression) : bool
    {
        return $expression instanceof PropertyFetch
            || $expression instanceof ClassConstFetch;
    }
}
