<?php

declare(strict_types=1);

namespace LanguageServer\Test\Server;

use LanguageServer\Exception\LanguageServerException;
use LanguageServer\Server\ResponseMessage;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ResponseMessageTest extends TestCase
{
    public function testCreateFromResult()
    {
        $result = new stdClass();

        $subject = ResponseMessage::createSuccessfulResponse($result, 1);

        $this->assertSame($result, $subject->result);
        $this->assertEquals(1, $subject->id);
        $this->assertNull($subject->error);
    }

    public function testCreateFromException()
    {
        $result = new LanguageServerException('Test Exception');

        $subject = ResponseMessage::createErrorResponse($result, 1);

        $this->assertSame('Test Exception', $subject->error);
        $this->assertEquals(1, $subject->id);
        $this->assertNull($subject->result);
    }
}
