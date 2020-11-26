<?php

declare(strict_types=1);

namespace LanguageServer\Completion\Completors;

use LanguageServer\Completion\ReflectionCompletor;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

use function array_map;
use function array_values;

class ClassConstantCompletor implements ReflectionCompletor
{
    /**
     * {@inheritdoc}
     */
    public function complete(Node $expression, ReflectionClass $reflection): array
    {
        return array_values(array_map(
            static function (ReflectionClassConstant $constant) {
                return new CompletionItem(
                    $constant->getName(),
                    CompletionItemKind::VALUE,
                    null,
                    $constant->getDocComment()
                );
            },
            $reflection->getReflectionConstants()
        ));
    }

    public function supports(Node $expression): bool
    {
        return $expression instanceof ClassConstFetch;
    }
}
