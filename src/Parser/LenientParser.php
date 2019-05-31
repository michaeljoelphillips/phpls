<?php

declare(strict_types=1);

namespace LanguageServer\Parser;

use PhpParser\ErrorHandler;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Parser;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class LenientParser implements Parser
{
    private $wrapped;

    public function __construct(Parser $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    public function parse(string $code, ErrorHandler $errorHandler = null)
    {
        $result = $this->wrapped->parse($code, new Collecting());

        if (null === $result) {
            return [];
        }

        return $result;
    }
}
