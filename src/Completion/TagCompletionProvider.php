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
use function strlen;
use function strpos;

class TagCompletionProvider implements CompletionProvider
{
    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;
    }

    /**
     * @return CompletionItem[]
     */
    public function complete(NodeAbstract $expression, ReflectionClass $reflection) : array
    {
        if (strlen($expression->getLast()) < 3) {
            return [];
        }

        $reader       = Reader::fromFile($this->tagFilePath(), true);
        $matchingTags = $reader->filter(static function (Tag $tag) use ($expression) : bool {
            return ($tag->fields['kind'] === 'c' || $tag->fields['kind'] === 'i') && strpos($tag->name, $expression->getLast()) !== false;
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

    private function tagFilePath() : string
    {
        return $this->projectRoot . '/tags';
    }

    private function completionItemKind(string $kind) : int
    {
        switch ($kind) {
            case 'i':
                return CompletionItemKind::INTERFACE;
            case 'c':
            case 't':
                return CompletionItemKind::CLASS_;
            case 'f':
                return CompletionItemKind::FUNCTION;
        }
    }

    public function supports(NodeAbstract $expression) : bool
    {
        return $expression instanceof Name;
    }
}
