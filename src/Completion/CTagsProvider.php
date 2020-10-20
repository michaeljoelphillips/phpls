<?php

declare(strict_types=1);

namespace LanguageServer\Completion;

use CTags\Reader;
use CTags\Tag;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Name;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function file_exists;
use function strlen;
use function strpos;

class CTagsProvider implements CompletionProvider
{
    private string $projectRoot;

    private int $keywordLength;

    public function __construct(string $projectRoot, int $keywordLength)
    {
        $this->projectRoot   = $projectRoot;
        $this->keywordLength = $keywordLength;
    }

    private function resolveTagsFile() : ?string
    {
        if (file_exists($this->projectRoot . '/tags')) {
            return $this->projectRoot . '/tags';
        }

        return null;
    }

    /**
     * @return CompletionItem[]
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection) : array
    {
        $reader = Reader::fromFile($this->resolveTagsFile(), true);

        $matchingTags = $reader->filter(static function (Tag $tag) use ($expression) : bool {
            return ($tag->fields['kind'] === 'c' || $tag->fields['kind'] === 'i')
                && strpos($tag->name, $expression->getLast()) !== false;
        });

        $completionItems = [];
        foreach ($matchingTags as $tag) {
            $completionItems[] = new CompletionItem(
                $tag->name,
                $this->completionItemKind($tag->fields['kind']),
                $tag->fields['namespace'] ?? '',
            );
        }

        return $completionItems;
    }

    private function completionItemKind(string $kind) : int
    {
        switch ($kind) {
            case 'i':
                return CompletionItemKind::INTERFACE;
            case 'c':
            case 't':
                return CompletionItemKind::CLASS_;
        }
    }

    public function supports(NodeAbstract $expression) : bool
    {
        return $this->resolveTagsFile() !== null
            && $expression instanceof Name
            && strlen($expression->getLast()) >= $this->keywordLength;
    }
}
