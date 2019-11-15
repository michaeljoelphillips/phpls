<?php

declare(strict_types=1);

namespace LanguageServer\Method;

use DI\Container;
use LanguageServer\Method\RemoteMethodInterface;
use LanguageServerProtocol\CompletionOptions;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\SignatureHelpOptions;
use RuntimeException;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Initialize implements RemoteMethodInterface
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __invoke(array $params)
    {
        $this->setProjectRoot($params);

        $capabilities = new ServerCapabilities();
        $capabilities->completionProvider = new CompletionOptions(true, [':', '>']);
        $capabilities->signatureHelpProvider = new SignatureHelpOptions(['(', ',']);

        return $capabilities;
    }

    private function setProjectRoot(array $params): void
    {
        if (null === ($params['rootUri'] ?? null)) {
            throw new RuntimeException('The project root was not specified');
        }

        $this->container->set('project_root', str_replace('file://', '', $params['rootUri']));
    }
}
