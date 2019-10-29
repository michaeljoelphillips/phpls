<?php

declare(strict_types=1);

namespace LanguageServer\Server;

use Throwable;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ResponseMessage extends Message
{
    public $id;
    public $result;
    public $error;

    public static function createErrorResponse(Throwable $exception, int $id): self
    {
        $message = new self();
        $message->id = $id;
        $message->error = $exception->getMessage();

        return $message;
    }

    public static function createSuccessfulResponse(object $result, int $id): self
    {
        $message = new self();
        $message->id = $id;
        $message->result = $result;

        return $message;
    }
}
