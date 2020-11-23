<?php

declare(strict_types=1);

namespace LanguageServer\Completion\Providers;

use LanguageServer\Completion\TypeBasedCompletionProvider;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;

use function array_column;
use function array_filter;
use function array_map;
use function array_values;
use function preg_match_all;
use function sprintf;

class MethodDocTagProvider implements TypeBasedCompletionProvider
{
    /**
     * {@inheritdoc}
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array
    {
        $methods = $this->parseMethodsInDocblock($reflection->getDocComment());

        $methods = array_filter($methods, static function (array $method) use ($expression): bool {
            if ($expression instanceof PropertyFetch) {
                return $method[1] !== 'static';
            }

            return $method[1] === 'static';
        });

        return $this->mapMethodsToCompletionItems($methods);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseMethodsInDocblock(string $docblock): array
    {
        $matches         = [];
        $numberOfMatches = preg_match_all('/@method (static)? ?([\w\\\]+) ((\w+)\(.*\))/', $docblock, $matches);

        if ((bool) $numberOfMatches === false) {
            return [];
        }

        $parsedMethods = [];
        for ($i = 0; $i < $numberOfMatches; $i++) {
            $parsedMethods[] = array_column($matches, $i);
        }

        return $parsedMethods;
    }

    /**
     * @param array<int, array<int, string>> $methods
     *
     * @return CompletionItem[]
     */
    private function mapMethodsToCompletionItems(array $methods): array
    {
        return array_values(array_map(
            static function (array $method): CompletionItem {
                $modifiers = $method[1] === 'static' ? 'public static' : 'public';
                $signature = sprintf('%s %s: %s', $modifiers, $method[3], $method[2]);

                return new CompletionItem(
                    $method[4],
                    CompletionItemKind::METHOD,
                    $signature
                );
            },
            $methods
        ));
    }

    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof PropertyFetch
            || $expression instanceof ClassConstFetch;
    }
}
