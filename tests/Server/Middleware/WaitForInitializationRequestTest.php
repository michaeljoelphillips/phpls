<?php

declare(strict_types=1);

namespace Tests\Server\Middleware;

use LanguageServer\Exception\ServerNotInitializedException;
use LanguageServer\Server\Middleware\WaitForInitializationRequest;
use LanguageServer\Server\Protocol\RequestMessage;
use PHPUnit\Framework\TestCase;

class WaitForInitializationRequestTest extends TestCase
{
    public function testWhenInitializationRequestWasNotSentFirst()
    {
        $subject = new WaitForInitializationRequest();

        $next = function () {
            $this->fail('The next middleware should never be called');
        };

        $this->expectException(ServerNotInitializedException::class);

        $subject->__invoke(new RequestMessage(1, 'textDocument/completion', []), $next);
    }

    public function testWhenInitializationRequestWasSentFirst()
    {
        $subject = new WaitForInitializationRequest();

        $next = function () {
            $this->addToAssertionCount(1);
        };

        $subject->__invoke(new RequestMessage(1, 'initialize', []), $next);
        $subject->__invoke(new RequestMessage(1, 'textDocument/didOpen', []), $next);
    }
}
