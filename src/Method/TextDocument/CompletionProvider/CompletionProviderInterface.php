<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServer\Parser\ParsedDocument;
use LanguageServerProtocol\CompletionItem;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
interface CompletionProviderInterface
{
    /**
     * @return CompletionItem[]
     */
    public function complete(ParsedDocument $document, Expr $expression): array;

    /**
     * @param Expr $expression
     *
     * @return bool
     */
    public function supports(NodeAbstract $expression): bool;
}
