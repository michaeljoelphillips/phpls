<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use LanguageServerProtocol\CompletionItem;
use PhpParser\Node;
use Roave\BetterReflection\Reflection\ReflectionClass;

interface ReflectionCompletor
{
    /**
     * @return array<int, CompletionItem>
     */
    public function complete(Node $expression, ReflectionClass $reflection): array;

    public function supports(Node $node): bool;
}
