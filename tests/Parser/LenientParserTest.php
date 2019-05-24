<?php

declare(strict_types=1);

namespace LanguageServer\Test\Parser;

use LanguageServer\Parser\LenientParser;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class LenientParserTest extends TestCase
{
    public function testParse(): void
    {
        $code = '<?php echo "Hi";';

        $parser = $this->createMock(Parser::class);

        $parser
            ->expects($this->once())
            ->method('parse')
            ->with($code, new Collecting());

        $subject = new LenientParser($parser);

        $subject->parse($code);
    }
}
