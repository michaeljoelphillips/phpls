<?php

declare(strict_types=1);

namespace LanguageServer\LSP\Response;

use LanguageServer\RPC\JsonRpcResponse;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class InitializeResponse extends JsonRpcResponse
{
    public function __toString()
    {
        $result = [
            'codeActionProvider' => null,
            'codeLensProvider' => null,
            'completionProvider' => [
                'resolveProvider' => false,
                'triggerCharacters' => ['$', '>'],
            ],
            'definitionProvider' => false,
            'dependenciesProvider' => null,
            'documentFormattingProvider' => null,
            'documentHighlightProvider' => null,
            'documentOnTypeFormattingProvider' => null,
            'documentRangeFormattingProvider' => null,
            'documentSymbolProvider' => false,
            'hoverProvider' => false,
            'referencesProvider' => false,
            'renameProvider' => null,
            'signatureHelpProvider' => ['triggerCharacters' => ['(', ',']],
            'textDocumentSync' => 0,
            'workspaceSymbolProvider' => false,
            'xdefinitionProvider' => false,
            'xdependenciesProvider' => false,
            'xworkspaceReferencesProvider' => false,
        ];

        return $this->prepare($result);
    }
}
