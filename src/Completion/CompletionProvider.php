<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use PhpParser\NodeAbstract;

interface CompletionProvider
{
    public function supports(NodeAbstract $expression): bool;
}
