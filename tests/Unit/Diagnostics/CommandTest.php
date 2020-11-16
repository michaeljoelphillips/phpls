<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Diagnostics;

use LanguageServer\Diagnostics\Command;
use LanguageServer\ParsedDocument;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use RuntimeException;

use function Clue\React\Block\await;
use function file_get_contents;
use function sprintf;

class CommandTest extends TestCase
{
    private const FIXTURES      = __DIR__ . '/../../fixtures';
    private const DOCUMENT_PATH = self::FIXTURES . '/NoConstructor.php';

    private LoopInterface $loop;

    public function setUp(): void
    {
        $this->loop = Factory::create();
    }

    public function testExecute(): void
    {
        $subject = new class ($this->loop, self::FIXTURES) extends Command {
            public function getCommand(ParsedDocument $document): string
            {
                return sprintf('cat %s', $document->getPath());
            }
        };

        $document = new ParsedDocument(sprintf('file://%s', self::DOCUMENT_PATH), '<?php', [], []);
        $promise  = $subject->execute($document);
        $result   = await($promise, $this->loop, 5.0);

        self::assertEquals(file_get_contents(self::DOCUMENT_PATH), $result);
    }

    public function testExecuteWithInput(): void
    {
        $subject = new class ($this->loop, self::FIXTURES) extends Command {
            public function getCommand(ParsedDocument $document): string
            {
                return 'cat';
            }

            protected function input(ParsedDocument $document): ?string
            {
                return $document->getSource();
            }
        };

        $document = new ParsedDocument(sprintf('file://%s', self::DOCUMENT_PATH), '<?php', [], []);
        $promise  = $subject->execute($document);
        $result   = await($promise, $this->loop, 5.0);

        self::assertEquals('<?php', $result);
    }

    public function testIsRunning(): void
    {
        $subject = new class ($this->loop, self::FIXTURES) extends Command {
            public function getCommand(ParsedDocument $document): string
            {
                return 'ls';
            }
        };

        $document = new ParsedDocument(sprintf('file://%s', self::DOCUMENT_PATH), '<?php', [], []);

        $subject->execute($document);

        self::assertTrue($subject->isRunning());
    }

    public function testTerminate(): void
    {
        $subject = new class ($this->loop, self::FIXTURES) extends Command {
            public function getCommand(ParsedDocument $document): string
            {
                return 'ls';
            }
        };

        $document = new ParsedDocument(sprintf('file://%s', self::DOCUMENT_PATH), '<?php', [], []);

        $promise = $subject->execute($document);
        $subject->terminate();

        $this->expectException(RuntimeException::class);

        await($promise, $this->loop);
    }
}
