<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Server\Serializer;

use LanguageServer\Server\Serializer\JsonRpcEncoder;
use PHPUnit\Framework\TestCase;

class JsonRpcEncoderTest extends TestCase
{
    public function testEncode(): void
    {
        $subject = new JsonRpcEncoder();
        $result  = $subject->encode(['id' => 1], JsonRpcEncoder::FORMAT);

        $this->assertEquals($result, "Content-Type: application/vscode-jsonrpc; charset=utf8\r\nContent-Length: 8\r\n\r\n{\"id\":1}");
    }

    public function testDecode(): void
    {
        $subject = new JsonRpcEncoder();
        $result  = $subject->decode("Content-Type: application/vscode-jsonrpc; charset=utf8\r\nContent-Length: 8\r\n\r\n{\"id\":1}", JsonRpcEncoder::FORMAT);

        $this->assertEquals($result, ['id' => 1]);
    }

    public function testSupportsEncoding(): void
    {
        $subject = new JsonRpcEncoder();

        $this->assertTrue($subject->supportsEncoding(JsonRpcEncoder::FORMAT));
    }

    public function testSupportsDecoding(): void
    {
        $subject = new JsonRpcEncoder();

        $this->assertTrue($subject->supportsDecoding(JsonRpcEncoder::FORMAT));
    }
}
