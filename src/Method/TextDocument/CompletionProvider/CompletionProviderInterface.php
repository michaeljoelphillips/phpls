<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServerProtocol\CompletionItem;
use PhpParser\Node\Expr;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
interface CompletionProviderInterface
{
    /**
     * @return CompletionItem[]
     */
    public function complete(Expr $expression, ReflectionClass $reflection): array;

    /**
     * @param Expr $expression
     *
     * @return bool
     */
    public function supports(Expr $expression): bool;
}
