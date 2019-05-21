<?php

declare(strict_types=1);

namespace LanguageServer\Server;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ResponseMessage extends Message
{
    public $id;
    public $result;
    public $error;
}
