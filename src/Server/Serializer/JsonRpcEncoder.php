<?php

declare(strict_types=1);

namespace LanguageServer\Server\Serializer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use function explode;
use function sprintf;
use function strlen;

class JsonRpcEncoder extends JsonEncoder
{
    public const FORMAT             = 'jsonrpc';
    private const HEADER_TERMINATOR = "\r\n\r\n";

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = [])
    {
        $result = parent::encode($data, 'json', $context);

        $headers = sprintf(
            "%s\r\n%s",
            'Content-Type: application/vscode-jsonrpc; charset=utf8',
            'Content-Length: ' . strlen($result)
        );

        return sprintf('%s%s%s', $headers, self::HEADER_TERMINATOR, $result);
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = [])
    {
        [$headers, $content] = explode(self::HEADER_TERMINATOR, $data);

        return parent::decode($content, 'json', $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format)
    {
        return $format === self::FORMAT;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format)
    {
        return $format === self::FORMAT;
    }
}
