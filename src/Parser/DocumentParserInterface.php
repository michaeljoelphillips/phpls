<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use LanguageServer\TextDocument;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
interface DocumentParserInterface
{
    public function parse(TextDocument $document): ParsedDocument;
}
