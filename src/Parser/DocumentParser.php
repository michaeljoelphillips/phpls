<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use LanguageServer\TextDocument;

interface DocumentParser
{
    public function parse(TextDocument $document) : ParsedDocument;
}
