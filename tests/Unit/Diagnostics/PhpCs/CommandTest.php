<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Diagnostics\PhpCs;

use LanguageServer\Diagnostics\PhpCs\Command;
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
            'php vendor/bin/phpcs -q --report=json --no-colors --stdin-path=/tmp/src/Foo.php -',
            $command
        );
    }
}
