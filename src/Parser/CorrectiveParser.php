<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use PhpParser\Error;
use PhpParser\ErrorHandler;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Parser;
use Psr\Log\LoggerInterface;
use function array_slice;
use function explode;
use function implode;
use function preg_replace_callback_array;
use function sprintf;
use function trim;
use const PHP_EOL;

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
    private LoggerInterface $logger;

    public function __construct(Parser $wrappedParser, LoggerInterface $logger)
    {
        $this->wrappedParser = $wrappedParser;
        $this->logger        = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $source, ?ErrorHandler $errorHandler = null)
    {
        $errorHandler    = new Collecting();
        $completedSource = $this->amendIncompleteSource($source);
        $result          = $this->wrappedParser->parse($completedSource, $errorHandler);

        if ($errorHandler->hasErrors()) {
            foreach ($errorHandler->getErrors() as $error) {
                $this->logger->debug(
                    sprintf('Parse Error: %s', $error->getMessage()),
                    [
                        'lines' => $this->formatOffendingLines($completedSource, $error),
                    ],
                );
            }
        }

        if ($result === null) {
            $this->logger->error('The parser failed to parse the source');

            return [];
        }

        return $result;
    }

    private function amendIncompleteSource(string $source) : string
    {
        return preg_replace_callback_array(
            [
                '/(\$|->|::)(\s+)(\$)/' => static fn ($match) => sprintf('%sstub%s%s', $match[1], $match[2], $match[3]),
                sprintf('/(\$|->|::)(\s*)(%s)/', implode('|', self::SYMBOLS)) => static fn ($match) => sprintf('%sstub%s%s', $match[1], $match[2], $match[3]),
                sprintf('/(\$|->|::)(\s+)(%s)/', implode('|', self::KEYWORDS)) => static fn ($match) => sprintf('%sstub;%s%s', $match[1], $match[2], $match[3]),
            ],
            $source
        );
    }

    private function formatOffendingLines(string $code, Error $error) : string
    {
        $lines = array_slice(
            explode(PHP_EOL, $code),
            $error->getStartLine() - 1,
            2
        );

        return trim(implode(PHP_EOL, $lines));
    }
}
