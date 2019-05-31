<?php

declare(strict_types=1);

namespace LanguageServer\Test\Parser;

use LanguageServer\Parser\LenientParser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class LenientParserTest extends TestCase
{
    public function testParseCollectsExceptions(): void
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $subject = new LenientParser($parser);

        $subject->parse('<?php $foo->;');

        // No exception was thrown
        $this->addToAssertionCount(1);
    }

    public function testParseReturnsEmptyArrayWhenParserFails()
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $subject = new LenientParser($parser);

        $subject->parse('<?php $foo(try {;');

        // No exception was thrown
        $this->addToAssertionCount(1);
    }
}
