<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use LanguageServer\ParsedDocument;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Parser as AstParser;

use function file_get_contents;

class DocumentParser
{
    private AstParser $astParser;

    public function __construct(AstParser $astParser)
    {
        $this->astParser = $astParser;
    }

    public function parse(string $uri, string $source): ParsedDocument
    {
        $errorHandler = new Collecting();
        $nodes        = $this->astParser->parse($source, $errorHandler) ?? [];

        return new ParsedDocument($uri, $source, $nodes, $errorHandler->getErrors());
    }

    public function parseFromFile(string $uri): ParsedDocument
    {
        $errorHandler = new Collecting();
        $source       = file_get_contents($uri) ?: '';
        $nodes        = $this->astParser->parse($source, $errorHandler) ?? [];

        return new ParsedDocument($uri, $source, $nodes, $errorHandler->getErrors(), true);
    }
}
