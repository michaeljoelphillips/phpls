<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use PhpParser\Error;
use PhpParser\ErrorHandler;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Parser;
use Psr\Log\LoggerInterface;
use function array_slice;
use function explode;
use function implode;
use function sprintf;
use function trim;
use const PHP_EOL;

class LenientParser implements Parser
{
    private Parser $wrapped;
    private LoggerInterface $logger;

    public function __construct(Parser $wrapped, LoggerInterface $logger)
    {
        $this->wrapped = $wrapped;
        $this->logger  = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $code, ?ErrorHandler $errorHandler = null)
    {
        $errorHandler = new Collecting();
        $result       = $this->wrapped->parse($code, $errorHandler);

        if ($errorHandler->hasErrors()) {
            foreach ($errorHandler->getErrors() as $error) {
                $this->logger->debug(
                    sprintf('Parse Error: %s', $error->getMessage()),
                    [
                        'lines' => $this->formatOffendingLines($code, $error),
                    ],
                );
            }
        }

        if ($result === null) {
            $this->logger->debug('Parse Error: No Results (Parsing completely failed)');

            return [];
        }

        return $result;
    }

    private function formatOffendingLines(string $code, Error $error) : string
    {
        $lines = array_slice(
            explode(PHP_EOL, $code),
            $error->getStartLine() - 1,
            2
        );

        return trim(implode(PHP_EOL, $lines));
    }
}
