<?php

declare(strict_types=1);

namespace Tests\RPC\Normalizers;

use LanguageServer\LSP\InitializeParams;
use LanguageServer\RPC\Normalizers\RequestMessageDenormalizer;
use LanguageServer\RPC\ResponseMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class RequestMessageDenormalizerTest extends TestCase
{
    private const INITIALIZE_REQUEST = <<<EOF
{
    "jsonrpc": "2.0",
    "id" : 1,
    "method": "textDocument/definition",
    "params": {
        "textDocument": {
            "uri": "file:///p%3A/mseng/VSCode/Playgrounds/cpp/use.cpp"
        },
        "position": {
            "line": 3,
            "character": 12
        }
    }
}
EOF;

    public function testDenormalize(): void
    {
        $subject = new RequestMessageDenormalizer();
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $subject->setDenormalizer($denormalizer);

        $denormalizer
            ->method('denormalize')
            ->willReturn(new InitializeParams());

        $request = json_decode(self::INITIALIZE_REQUEST, true);

        $result = $subject->denormalize($request, ResponseMessage::class, 'jsonrpc');
    }
}
