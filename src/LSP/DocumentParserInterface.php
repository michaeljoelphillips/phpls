<?php

declare(strict_types=1);

namespace LanguageServer\LSP;

use LanguageServer\LSP\ParsedDocument;
use LanguageServer\LSP\TextDocument;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
interface DocumentParserInterface
{
    public function parse(TextDocument $document): ParsedDocument;
}
