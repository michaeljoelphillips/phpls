<?php

declare(strict_types=1);

namespace LanguageServer\Method;

use DI\Container;
use LanguageServer\Exception\ServerNotInitialized;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use LanguageServerProtocol\CompletionOptions;
use LanguageServerProtocol\InitializeResult;
use LanguageServerProtocol\SaveOptions;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\SignatureHelpOptions;
use LanguageServerProtocol\TextDocumentSyncKind;
use LanguageServerProtocol\TextDocumentSyncOptions;
use Psr\Container\ContainerInterface;
use RuntimeException;
use function parse_url;
use function realpath;
use function urldecode;
use const PHP_URL_PATH;

class Initialize implements MessageHandler
{
    private ContainerInterface $container;
    private bool $wasInitialized = false;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $request, callable $next)
    {
        if ($request->method === 'initialize') {
            $this->wasInitialized = true;

            return new ResponseMessage($request, $this->getInitializeResult($request));
        }

        if ($this->wasInitialized === false && $request->method !== 'exit') {
            throw new ServerNotInitialized();
        }

        return $next($request);
    }

    public function getInitializeResult(Message $request) : InitializeResult
    {
        $this->setProjectRoot($request->params);

        $capabilities = new ServerCapabilities();

        $saveOptions      = new SaveOptions();
        $textDocumentSync = new TextDocumentSyncOptions();

        $saveOptions->includeText            = false;
        $textDocumentSync->openClose         = true;
        $textDocumentSync->willSave          = false;
        $textDocumentSync->save              = $saveOptions;
        $textDocumentSync->willSaveWaitUntil = false;
        $textDocumentSync->change            = TextDocumentSyncKind::FULL;

        $capabilities->hoverProvider                    = false;
        $capabilities->renameProvider                   = false;
        $capabilities->codeLensProvider                 = false;
        $capabilities->definitionProvider               = false;
        $capabilities->referencesProvider               = false;
        $capabilities->referencesProvider               = false;
        $capabilities->codeActionProvider               = false;
        $capabilities->xdefinitionProvider              = false;
        $capabilities->dependenciesProvider             = false;
        $capabilities->documentSymbolProvider           = false;
        $capabilities->workspaceSymbolProvider          = false;
        $capabilities->documentHighlightProvider        = false;
        $capabilities->documentFormattingProvider       = false;
        $capabilities->xworkspaceReferencesProvider     = false;
        $capabilities->documentRangeFormattingProvider  = false;
        $capabilities->documentOnTypeFormattingProvider = false;

        $capabilities->textDocumentSync      = $textDocumentSync;
        $capabilities->completionProvider    = new CompletionOptions(false, [':', '>']);
        $capabilities->signatureHelpProvider = new SignatureHelpOptions(['(', ',']);

        return new InitializeResult($capabilities);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function setProjectRoot(array $params) : void
    {
        if ($params['rootUri'] === null) {
            throw new RuntimeException('The project root was not specified');
        }

        $projectRoot = $this->parseProjectRootUri($params['rootUri']);

        $this->container->set('project_root', $projectRoot);
    }

    private function parseProjectRootUri(string $uri) : string
    {
        $path = realpath(urldecode(parse_url($uri, PHP_URL_PATH)));

        if ($path === false) {
            throw new RuntimeException('The specified project root does not exist');
        }

        return $path;
    }
}
