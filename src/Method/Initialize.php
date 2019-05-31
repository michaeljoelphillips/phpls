<?php

declare(strict_types=1);

namespace LanguageServer\Method;

use LanguageServerProtocol\CompletionOptions;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\SignatureHelpOptions;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Initialize
{
    public function __invoke(array $params): ServerCapabilities
    {
        $capabilities = new ServerCapabilities();
        $capabilities->completionProvider = new CompletionOptions(true, [':', '>']);
        $capabilities->signatureHelpProvider = new SignatureHelpOptions(['(', ',']);

        return $capabilities;
    }
}
