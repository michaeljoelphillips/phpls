<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use PhpParser\ErrorHandler;
use PhpParser\Parser;
use function implode;
use function preg_replace_callback_array;
use function sprintf;

class CorrectiveParser implements Parser
{
    private const KEYWORDS = [
        'do',
        '}',
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

    private const SYMBOLS = [
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
        '\/\=',
        '\$',
        '=>',
        '::',
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
    ];

    private Parser $wrappedParser;

    public function __construct(Parser $wrappedParser)
    {
        $this->wrappedParser = $wrappedParser;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $source, ?ErrorHandler $errorHandler = null)
    {
        $completedSource = $this->amendDocumentSource($source);

        return $this->wrappedParser->parse($completedSource);
    }

    private function amendDocumentSource(string $source) : string
    {
        return preg_replace_callback_array(
            [
                sprintf('/(->|::)(\s*)(%s)/', implode('|', self::SYMBOLS)) => static fn ($match) => sprintf('%sstub%s%s', $match[1], $match[2], $match[3]),
                sprintf('/(->|::)(\s+)(%s)/', implode('|', self::KEYWORDS)) => static fn ($match) => sprintf('%sstub;%s%s', $match[1], $match[2], $match[3]),
            ],
            $source
        );
    }
}
