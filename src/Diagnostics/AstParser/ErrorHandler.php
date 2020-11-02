<?php

declare(strict_types=1);

namespace LanguageServer\Diagnostics\AstParser;

use LanguageServer\Diagnostics\DiagnosticService;
use PhpParser\Error;
use PhpParser\ErrorHandler as ErrorHandlerInterface;

class ErrorHandler implements ErrorHandlerInterface
{
    private DiagnosticService $service;

    public function __construct(DiagnosticService $service)
    {
        $this->service = $service;
    }

    public function handleError(Error $error): void
    {
        $this->service->handleParserError($error);
    }
}
