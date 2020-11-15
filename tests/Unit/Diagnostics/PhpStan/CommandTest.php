<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Diagnostics\PhpStan;

use LanguageServer\Diagnostics\PhpStan\Command;
use LanguageServer\ParsedDocument;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;

class CommandTest extends TestCase
{
    public function testGetCommand(): void
    {
        $subject  = new Command($this->createMock(LoopInterface::class), '/tmp');
        $document = new ParsedDocument('file:///tmp/src/Foo.php', '<?php', [], [], true);
        $command  = $subject->getCommand($document);

        self::assertEquals(
            'php vendor/bin/phpstan analyse --no-progress --error-format=json --no-ansi /tmp/src/Foo.php',
            $command
        );
    }
}
