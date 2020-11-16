<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Parser;

use LanguageServer\Parser\MemoizingParser;
use PhpParser\Error;
use PhpParser\ErrorHandler;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class MemoizingParserTest extends TestCase
{
    public function testParseWhenCacheMisses(): void
    {
        $cache   = $this->createMock(CacheInterface::class);
        $parser  = $this->createMock(Parser::class);
        $subject = new MemoizingParser($cache, $parser);

        $cache
            ->method('has')
            ->willReturn(false);

        $cache
            ->expects($this->once())
            ->method('set')
            ->willReturn(false);

        $parser
            ->expects($this->once())
            ->method('parse');

        $subject->parse('<?php echo "Hello World";', null);
    }

    public function testParseWhenCacheHits(): void
    {
        $cache   = $this->createMock(CacheInterface::class);
        $parser  = $this->createMock(Parser::class);
        $subject = new MemoizingParser($cache, $parser);

        $cache
            ->method('has')
            ->willReturn(true);

        $cache
            ->method('get')
            ->willReturn([[], []]);

        $cache
            ->expects($this->never())
            ->method('set');

        $parser
            ->expects($this->never())
            ->method('parse');

        $subject->parse('<?php echo "Hello World";', null);
    }

    public function testParseCachesErrorsWithExternalErrorHandler(): void
    {
        $cache        = $this->createMock(CacheInterface::class);
        $parser       = $this->createMock(Parser::class);
        $errorHandler = $this->createMock(ErrorHandler::class);
        $subject      = new MemoizingParser($cache, $parser);

        $cache
            ->method('has')
            ->willReturn(true);

        $cache
            ->method('get')
            ->willReturn([[], [new Error('Test Error', [])]]);

        $cache
            ->expects($this->never())
            ->method('set');

        $parser
            ->expects($this->never())
            ->method('parse');

        $errorHandler
            ->expects($this->once())
            ->method('handleError');

        $subject->parse('<?php echo "Hello World";', $errorHandler);
    }
}
