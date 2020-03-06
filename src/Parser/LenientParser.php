<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use PhpParser\ErrorHandler;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Parser;

class LenientParser implements Parser
{
    private Parser $wrapped;

    public function __construct(Parser $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $code, ?ErrorHandler $errorHandler = null)
    {
        $result = $this->wrapped->parse($code, new Collecting());

        if ($result === null) {
            return [];
        }

        return $result;
    }
}
