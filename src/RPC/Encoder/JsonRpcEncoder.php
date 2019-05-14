<?php

declare(strict_types=1);

namespace LanguageServer\RPC\Encoder;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class JsonRpcEncoder extends JsonEncoder
{
    public const FORMAT = 'jsonrpc';
    private const HEADER_TERMINATOR = "\r\n\r\n";

    public function encode($data, $format, array $context = [])
    {
        $result = parent::encode($data, 'json', $context);

        $headers = sprintf(
            "%s\r\n%s",
            'Content-Type: application/vscode-jsonrpc; charset=utf8',
            'Content-Length: '.strlen($result),
        );

        return sprintf("%s%s%s", $headers, self::HEADER_TERMINATOR, $result);
    }

    public function decode($data, $format, array $context = [])
    {
        [$headers, $content] = explode(self::HEADER_TERMINATOR, $data);

        return parent::decode($content, 'json', $context);
    }

    public function supportsEncoding($format)
    {
        return self::FORMAT === $format;
    }

    public function supportsDecoding($format)
    {
        return self::FORMAT === $format;
    }
}
