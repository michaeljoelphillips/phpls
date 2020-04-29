<?php

declare(strict_types=1);

namespace LanguageServer\Test\Server\Protocol;

use Exception;
use LanguageServer\Server\Exception\InvalidRequest;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Server\Protocol\ResponseMessage;
use PHPUnit\Framework\TestCase;
use stdClass;

class ResponseMessageTest extends TestCase
{
    public function testResponseMessageWithResult() : void
    {
        $request = new RequestMessage(1, 'textDocument/completion', []);

        $result  = new stdClass();
        $subject = new ResponseMessage($request, $result);

        $this->assertNull($subject->error);
        $this->assertEquals(1, $subject->id);
        $this->assertSame($result, $subject->result);
    }

    public function testResponseMessageWithGenericException() : void
    {
        $request = new RequestMessage(1, 'textDocument/completion', []);

        $exception = new Exception('Test Exception');
        $subject   = new ResponseMessage($request, $exception);

        $this->assertNull($subject->result);
        $this->assertEquals(1, $subject->id);
        $this->assertEquals(-32603, $subject->error->code);
    }

    public function testResponseMessageWithException() : void
    {
        $request = new RequestMessage(1, 'textDocument/completion', []);

        $exception = new InvalidRequest();
        $subject   = new ResponseMessage($request, $exception);

        $this->assertNull($subject->result);
        $this->assertEquals(1, $subject->id);
        $this->assertEquals(-32600, $subject->error->code);
    }
}
