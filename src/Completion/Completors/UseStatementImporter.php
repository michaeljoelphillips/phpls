<?php

declare(strict_types=1);

namespace LanguageServer\Completion\Completors;

use LanguageServer\Completion\DocumentCompletor;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\TextEdit;
use PhpParser\Node;

use function array_key_last;
use function assert;
use function count;
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

        $nextAvailableLine = $this->getNextUseStatementLine($document);

        foreach ($completionItems as $completionItem) {
            if ($this->itemCanBeImported($completionItem) === false) {
                continue;
            }

            $newUseStatement = $this->buildUseStatement($completionItem);

            $completionItem->additionalTextEdits = [
                new TextEdit(
                    new Range(
                        new Position($nextAvailableLine, 0),
                        new Position($nextAvailableLine, strlen($newUseStatement) - 1),
                    ),
                    $newUseStatement
                ),
            ];
        }

        return $completionItems;
    }

    private function getNextUseStatementLine(ParsedDocument $document): int
    {
        $useStatements = $document->getUseStatements();

        if (count($useStatements) > 0) {
            $lastUseStatement = $useStatements[array_key_last($useStatements)];

            return $lastUseStatement->uses[array_key_last($lastUseStatement->uses)]->getEndLine();
        }

        $namespaceNode = $document->getNamespaceNode();

        return $namespaceNode->getStartLine() + 1;
    }

    private function itemCanBeImported(CompletionItem $completionItem): bool
    {
        return in_array($completionItem->kind, self::IMPORTABLE_COMPLETION_ITEM_KINDS);
    }

    private function buildUseStatement(CompletionItem $completionItem): string
    {
        switch ($completionItem->kind) {
            case CompletionItemKind::CLASS_:
            case CompletionItemKind::INTERFACE:
                $statement = sprintf('use %s\\%s;%s', $completionItem->detail, $completionItem->label, PHP_EOL);
                break;

            case CompletionItemKind::FUNCTION:
                $statement = sprintf('use function %s\\%s;%s', $completionItem->detail, $completionItem->label, PHP_EOL);
        }

        assert(isset($statement));

        return $statement;
    }

    public function supports(Node $node): bool
    {
        return $this->completor->supports($node);
    }
}
