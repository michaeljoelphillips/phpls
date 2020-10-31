<?php

declare(strict_types=1);

namespace LanguageServer\MessageHandler;

use DI\Container;
use LanguageServer\Server\Exception\ServerNotInitialized;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Server\Protocol\ResponseMessage;
use LanguageServerProtocol\CompletionOptions;
use LanguageServerProtocol\InitializeResult;
use LanguageServerProtocol\SaveOptions;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\SignatureHelpOptions;
use LanguageServerProtocol\TextDocumentSyncKind;
use LanguageServerProtocol\TextDocumentSyncOptions;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function assert;
use function parse_url;
use function realpath;
use function sprintf;
use function urldecode;

use const PHP_URL_PATH;

class Initialize implements MessageHandler
{
    private Container $container;
    private LoggerInterface $logger;
    private bool $hasBeenInitialized = false;

    public function __construct(Container $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger    = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $request, callable $next)
    {
        if ($request->method === 'initialize') {
            assert($request instanceof RequestMessage);

            $this->hasBeenInitialized = true;

            return new ResponseMessage($request, $this->getInitializeResult($request));
        }

        if ($this->hasBeenInitialized === false && $request->method !== 'exit') {
            throw new ServerNotInitialized();
        }

        return $next($request);
    }

    private function getInitializeResult(RequestMessage $request): InitializeResult
    {
        assert($request->params !== null);
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
        $capabilities->codeLensProvider                 = null;
        $capabilities->implementationProvider           = false;
        $capabilities->typeDefinitionProvider           = false;
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
        $capabilities->documentOnTypeFormattingProvider = null;

        $capabilities->textDocumentSync      = $textDocumentSync;
        $capabilities->completionProvider    = new CompletionOptions(false, ['$', ':', '>']);
        $capabilities->signatureHelpProvider = new SignatureHelpOptions(['(', ',']);

        return new InitializeResult($capabilities);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function setProjectRoot(array $params): void
    {
        if ($params['rootUri'] === null) {
            throw new RuntimeException('The project root was not specified');
        }

        $projectRoot = $this->parseProjectRootUri($params['rootUri']);

        $this->logger->debug(sprintf('Setting the project root: %s', $projectRoot));

        $this->container->set('project_root', $projectRoot);
    }

    private function parseProjectRootUri(string $uri): string
    {
        $url = parse_url($uri, PHP_URL_PATH);

        if ($url === false || $url === null) {
            throw new RuntimeException('The specified project root does not exist');
        }

        $path = realpath(urldecode($url));

        if ($path === false) {
            throw new RuntimeException('The specified project root does not exist');
        }

        return $path;
    }
}
