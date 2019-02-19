<?php

declare(strict_types=1);

namespace LanguageServer\LSP\Response;

use LanguageServer\RPC\JsonRpcResponse;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class SignatureHelpResponse extends JsonRpcResponse
{
    /** @var array */
    protected $result;

    public function __construct(int $id, array $body)
    {
        parent::__construct($id);

        $this->result = $body;
    }

    public function __toString()
    {
        return $this->prepare($this->result);
    }
}
