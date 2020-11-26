<?php

declare(strict_types=1);

namespace LanguageServer\Completion\Completors;

use React\Socket\Server;
use LanguageServer\Completion\DocumentCompletor;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\TextEdit;
use PhpParser\Node;

use function in_array;
use function sprintf;
use function strlen;

use const PHP_EOL;

class UseStatementImporter implements DocumentCompletor
{
    private const IMPORTABLE_COMPLETION_ITEM_KINDS = [
        CompletionItemKind::CLASS_,
        CompletionItemKind::FUNCTION,
    ];

    private DocumentCompletor $completor;

    public function __construct(DocumentCompletor $completor)
    {
        $this->completor = $completor;
    }

    /**
     * {@inheritdoc}
     */
    public function complete(Node $expression, ParsedDocument $document): array
    {
        $completionItems = $this
            ->completor
            ->complete($expression, $document);

        $useStatements = $document->getUseStatements();

        foreach ($completionItems as $completionItem) {
            if ($this->itemCanBeImported($completionItem) !== true) {
                continue;
            }

            $lastUseStatement = $useStatements[0];
            $newUseStatement  = $this->buildUseStatement($completionItem);

            $completionItem->additionalTextEdits = [
                new TextEdit(
                    new Range(
                        new Position($lastUseStatement->uses[0]->getEndLine() - 2, 0),
                        new Position($lastUseStatement->uses[0]->getEndLine() - 2, strlen($newUseStatement) - 1),
                    ),
                    $newUseStatement
                ),
            ];
        }

        return $completionItems;
    }

    private function itemCanBeImported(CompletionItem $completionItem): bool
    {
        return in_array($completionItem->kind, self::IMPORTABLE_COMPLETION_ITEM_KINDS);
    }

    private function buildUseStatement(CompletionItem $completionItem): string
    {
        return sprintf('%suse %s\\%s;', PHP_EOL, $completionItem->detail, $completionItem->label);
    }

    public function supports(Node $node): bool
    {
        return $this->completor->supports($node);
    }
}
