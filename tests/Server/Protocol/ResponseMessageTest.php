<?php

declare(strict_types=1);

namespace LanguageServer\Test\Server\Protocol;

use Exception;
use LanguageServer\Exception\InvalidRequestException;
use LanguageServer\Exception\LanguageServerException;
use LanguageServer\Server\Protocol\RequestMessage;
use LanguageServer\Server\Protocol\ResponseError;
use LanguageServer\Server\Protocol\ResponseMessage;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ResponseMessageTest extends TestCase
{
    public function testResponseMessageWithResult()
    {
        $request = new RequestMessage(1, 'textDocument/completion', []);

        $result = new stdClass();
        $subject = new ResponseMessage($request, $result);

        $this->assertNull($subject->error);
        $this->assertEquals(1, $subject->id);
        $this->assertSame($result, $subject->result);
    }

    public function testResponseMessageWithGenericException()
    {
        $request = new RequestMessage(1, 'textDocument/completion', []);

        $exception = new Exception('Test Exception');
        $subject = new ResponseMessage($request, $exception);

        $this->assertNull($subject->result);
        $this->assertEquals(1, $subject->id);
        $this->assertEquals(-32603, $subject->error->code);
    }

    public function testResponseMessageWithException()
    {
        $request = new RequestMessage(1, 'textDocument/completion', []);

        $exception = new InvalidRequestException();
        $subject = new ResponseMessage($request, $exception);

        $this->assertNull($subject->result);
        $this->assertEquals(1, $subject->id);
        $this->assertEquals(-32600, $subject->error->code);
    }
}
