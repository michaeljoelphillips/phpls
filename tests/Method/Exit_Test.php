<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method;

use LanguageServer\Method\Exit_;
use LanguageServer\TextDocumentRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Exit_Test extends TestCase
{
    public function testExit()
    {
        $registry = $this->createMock(TextDocumentRegistry::class);

        $registry
            ->expects($this->once())
            ->method('clear');

        $subject = new Exit_($registry);

        $subject->__invoke();
    }
}
