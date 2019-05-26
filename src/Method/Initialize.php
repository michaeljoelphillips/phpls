<?php

declare(strict_types=1);

namespace LanguageServer\Method;

use LanguageServerProtocol\CompletionOptions;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\SignatureHelpOptions;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Initialize
{
    public function __invoke(array $params): PromiseInterface
    {
        $capabilities = new ServerCapabilities();
        $capabilities->completionProvider = new CompletionOptions(true, [':', '>']);
        $capabilities->signatureHelpProvider = new SignatureHelpOptions(['(', ',']);

        return $this->deferred($capabilities);
    }

    private function deferred(ServerCapabilities $capabilities): Promise
    {
        $deferred = new Deferred();
        $deferred->resolve($capabilities);

        return $deferred->promise();
    }
}
