<?php

declare(strict_types=1);

namespace LanguageServer\Server\Protocol;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class RequestMessage extends Message
{
    public $id;
    public $method;
    public $params;
}
