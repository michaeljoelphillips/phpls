<?php

declare(strict_types=1);

namespace LanguageServer\RPC;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class JsonRpcResponse
{
    /** @var int */
    protected $id;

    /** @var array */
    protected $result = [];

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    protected function prepare(array $result): string
    {
        $body = json_encode([
            'id' => $this->id,
            'jsonrpc' => '2.0',
            'result' => $result,
        ]);

        $headers = $this->headers($body);

        return sprintf("%s\r\n\r\n%s", $headers, $body);
    }

    protected function headers(string $body): string
    {
        return sprintf(
            "%s\r\n%s",
            'Content-Type: application/vscode-jsonrpc; charset=utf8',
            'Content-Length: '.strlen($body),
        );
    }
}
