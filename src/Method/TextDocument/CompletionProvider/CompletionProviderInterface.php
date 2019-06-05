<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServerProtocol\CompletionItem;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
interface CompletionProviderInterface
{
    /**
     * @return CompletionItem[]
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection): array;

    /**
     * @param NodeAbstract $expression
     *
     * @return bool
     */
    public function supports(NodeAbstract $expression): bool;
}
