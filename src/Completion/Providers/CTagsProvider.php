<?php

declare(strict_types=1);

namespace LanguageServer\Completion\Providers;

use CTags\Reader;
use LanguageServer\Completion\DocumentBasedCompletionProvider;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\TextEdit;
use PhpParser\Node\Name;
use PhpParser\NodeAbstract;

use function assert;
use function file_exists;
use function strlen;

class CTagsProvider implements DocumentBasedCompletionProvider
{
    private string $projectRoot;

    private int $keywordLength;

    public function __construct(string $projectRoot, int $keywordLength)
    {
        $this->projectRoot   = $projectRoot;
        $this->keywordLength = $keywordLength;
    }

    private function resolveTagsFile(): ?string
    {
        if (file_exists($this->projectRoot . '/tags')) {
            return $this->projectRoot . '/tags';
        }

        return null;
    }

    private function getTagReader(): ?Reader
    {
        $tagFile = $this->resolveTagsFile();

        if ($tagFile === null) {
            return null;
        }

        return Reader::fromFile($tagFile, true);
    }

    /**
     * @return CompletionItem[]
     */
    public function complete(NodeAbstract $expression, ParsedDocument $document): array
    {
        assert($expression instanceof Name);

        $reader = $this->getTagReader();

        if ($reader === null) {
            return [];
        }

        $matchingTags = $reader->partialMatch($expression->getLast());

        $completionItems = [];
        foreach ($matchingTags as $tag) {
            $completionItems[] = new CompletionItem(
                $tag->name,
                $this->completionItemKind($tag->fields['kind']),
                $tag->fields['namespace'] ?? '',
                null,
                null,
                null,
                null,
                null,
                [new TextEdit(new Range(new Position(3, 0), new Position(3, 15)), 'use Hello\World;')]
            );
        }

        return $completionItems;
    }

    private function completionItemKind(?string $kind): ?int
    {
        switch ($kind) {
            case 'i':
                return CompletionItemKind::INTERFACE;

            case 'c':
            case 't':
                return CompletionItemKind::CLASS_;

            case 'f':
                return CompletionItemKind::FUNCTION;

            default:
                return null;
        }
    }

    public function supports(NodeAbstract $expression): bool
    {
        return $this->resolveTagsFile() !== null
            && $expression instanceof Name
            && strlen($expression->getLast()) >= $this->keywordLength;
    }
}
