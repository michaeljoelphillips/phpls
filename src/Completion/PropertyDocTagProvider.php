<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;

use function array_column;
use function array_map;
use function array_values;
use function preg_match_all;

class PropertyDocTagProvider implements CompletionProvider
{
    /**
     * {@inheritdoc}
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array
    {
        $properties = $this->parsePropertiesInDocblock($reflection->getDocComment());

        return $this->mapPropertiesToCompletionItems($properties);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parsePropertiesInDocblock(string $docblock): array
    {
        $matches         = [];
        $numberOfMatches = preg_match_all('/@property ([\w,\\\]+) \$?(\w+) ?(.*)/', $docblock, $matches);

        if ((bool) $numberOfMatches === false) {
            return [];
        }

        $parsedProperties = [];
        for ($i = 0; $i < $numberOfMatches; $i++) {
            $parsedProperties[] = array_column($matches, $i);
        }

        return $parsedProperties;
    }

    /**
     * @param array<int, array<int, string>> $properties
     *
     * @return CompletionItem[]
     */
    private function mapPropertiesToCompletionItems(array $properties): array
    {
        return array_values(array_map(
            static function (array $property): CompletionItem {
                return new CompletionItem(
                    $property[2],
                    CompletionItemKind::PROPERTY,
                    $property[1],
                    $property[3]
                );
            },
            $properties
        ));
    }

    public function supports(NodeAbstract $expression): bool
    {
        return $expression instanceof PropertyFetch && ! $expression->var instanceof MethodCall;
    }
}
