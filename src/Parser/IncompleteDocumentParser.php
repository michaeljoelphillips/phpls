<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use LanguageServer\TextDocument;
use PhpParser\Parser;
use function implode;
use function preg_replace;
use function sprintf;

class IncompleteDocumentParser implements DocumentParser
{
    private const REGEX_PARSER_TOKENS = [
        '->',
        ',',
        '&=',
        '\(',
        '\)',
        '\[',
        '\]',
        '&&',
        '\|\|',
        '__',
        '\?>',
        '\?\?',
        '\/\/',
        '\/\*',
        '#',
        '.=',
        '\'',
        '"',
        '{',
        '}',
        '\/\=',
        '\$',
        '=>',
        '::',
        'do',
        '\.\.\.',
        '\+\+',
        '==',
        '>=',
        '===',
        '\!=',
        '<>',
        '\!==',
        '<=',
        '<=>',
        '-=',
        '%=',
        '\*=',
        '\\',
        '\<\?=',
        '\<\?',
        '\|=',
        '::',
        '\+=',
        '\*\*',
        '\*\*=',
        '<<',
        '<<=',
        '>>',
        '>>=',
        '<<<',
        '\^=',
        'new',
        'abstract',
        'array',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'die',
        'extends',
        'final',
        'finally',
        'for',
        'foreach',
        'function',
        'cfunction',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'interface',
        'isset',
        'list',
        'and',
        'or',
        'xor',
        'namespace',
        'print',
        'private',
        'public',
        'protected',
        'require',
        'require_once',
        'return',
        'static',
        'parent',
        'self',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'yield',
        'yield from',
    ];

    private Parser $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function parse(TextDocument $document) : ParsedDocument
    {
        $documentSource = $this->amendDocumentSource($document->getSource());
        $nodes          = $this->parser->parse($documentSource);

        return new ParsedDocument($nodes, $document);
    }

    private function amendDocumentSource(string $source) : string
    {
        return preg_replace(
            sprintf('/(->|::)(\s*)(%s)/', implode('|', self::REGEX_PARSER_TOKENS)),
            '\1stub\2\3',
            $source
        );
    }
}
