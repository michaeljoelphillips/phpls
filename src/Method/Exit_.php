<?php

declare(strict_types=1);

namespace LanguageServer\Method;

use LanguageServer\TextDocumentRegistry;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Exit_
{
    private $registry;

    public function __construct(TextDocumentRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function __invoke(): void
    {
        $this->registry->clear();
    }
}
