<?php

declare(strict_types=1);

namespace LanguageServer\Server\Protocol;

use Throwable;

class ResponseMessage extends Message
{
    public ?int $id = null;

    /** @var mixed|ResponseError|null */
    public $result;

    public ?ResponseError $error = null;

    /**
     * @param mixed|Throwable|null $result
     */
    public function __construct(Message $request, $result)
    {
        if ($request instanceof RequestMessage) {
            $this->id = $request->id;
        }

        if ($result instanceof Throwable) {
            $this->error = new ResponseError($result);

            return;
        }

        $this->result = $result;
    }
}
