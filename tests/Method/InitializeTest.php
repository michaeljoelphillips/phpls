<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method;

use LanguageServer\Method\Initialize;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class InitializeTest extends TestCase
{
    public function testInitialize()
    {
        $subject = new Initialize();

        $result = $subject([]);

        $this->assertEquals([':', '>'], $result->completionProvider->triggerCharacters);
        $this->assertEquals(['(', ','], $result->signatureHelpProvider->triggerCharacters);
    }
}
