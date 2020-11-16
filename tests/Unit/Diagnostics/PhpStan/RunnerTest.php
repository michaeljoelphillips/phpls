<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Diagnostics\PhpStan;

use LanguageServer\Diagnostics\Command;
use LanguageServer\Diagnostics\PhpStan\Runner;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\DiagnosticSeverity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use function React\Promise\resolve;

use const PHP_INT_MAX;

class RunnerTest extends TestCase
{
    private const PHPSTAN_OUTPUT_WITH_ERRORS = <<<'JSON'
{
    "totals": {
        "errors": 0,
        "file_errors": 2
    },
    "files": {
        "/home/nomad/Code/phpls/tests/Unit/Diagnostics/PhpStan/RunnerTest.php": {
            "errors": 2,
            "messages": [
                {
                    "message": "Access to an undefined property LanguageServer\\Test\\Unit\\Diagnostics\\PhpStan\\RunnerTest::$command.",
                    "line": 14,
                    "ignorable": true
                },
                {
                    "message": "Access to an undefined property LanguageServer\\Test\\Unit\\Diagnostics\\PhpStan\\RunnerTest::$command.",
                    "line": 18,
                    "ignorable": true
                }
            ]
        }
    },
    "errors": []
}
JSON;

    private const PHPSTAN_OUTPUT_WITH_CONFIG_ERRORS = <<<OUTPUT
In NeonAdapter.php line 30:

  Error while loading ~/Code/phpls/phpstan.neon: Bad indentation on line 7, column 9.

analyse [--paths-file PATHS-FILE] [-c|--configuration CONFIGURATION] [-l|--level LEVEL] [--no-progress] [--debug] [-a|--autoload-file AUTOLOAD-FILE] [--error-format ERROR-FORMAT] [--generate-baseline [GENERATE-BASELINE]] [--memory-limit MEMORY-LIMIT] [--xdebug] [--fix] [--watch] [--pro] [--] [<paths>...]

OUTPUT;

    /** @var MockObject&Command */
    private $command;

    private Runner $subject;

    public function setUp(): void
    {
        $this->command = $this->createMock(Command::class);
        $this->subject = new Runner($this->command, $this->createMock(LoggerInterface::class), 'error');
    }

    public function testRunnerResolvesWithDiagnosticsWhenPhpStanReportsErrors(): void
    {
        $document = $this->createMock(ParsedDocument::class);

        $document
            ->method('hasErrors')
            ->willReturn(false);

        $document
            ->method('isPersisted')
            ->willReturn(true);

        $document
            ->method('getColumnPositions')
            ->will($this->onConsecutiveCalls(
                [5, 25],
                [13, 60]
            ));

        $this->command
            ->method('isRunning')
            ->willReturn(false);

        $this->command
            ->method('execute')
            ->willReturn(resolve(self::PHPSTAN_OUTPUT_WITH_ERRORS));

        $result = null;

        $this->subject
            ->run($document)
            ->then(static function (array $diagnostics) use (&$result): void {
                $result = $diagnostics;
            });

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(Diagnostic::class, $result);

        self::assertEquals(500, $result[0]->code);
        self::assertEquals(13, $result[0]->range->start->line);
        self::assertEquals(5, $result[0]->range->start->character);
        self::assertEquals(13, $result[0]->range->end->line);
        self::assertEquals(25, $result[0]->range->end->character);
        self::assertEquals(DiagnosticSeverity::ERROR, $result[0]->severity);
        self::assertEquals('Access to an undefined property LanguageServer\\Test\\Unit\\Diagnostics\\PhpStan\\RunnerTest::$command.', $result[0]->message);

        self::assertEquals(500, $result[1]->code);
        self::assertEquals(17, $result[1]->range->start->line);
        self::assertEquals(13, $result[1]->range->start->character);
        self::assertEquals(17, $result[1]->range->end->line);
        self::assertEquals(60, $result[1]->range->end->character);
        self::assertEquals(DiagnosticSeverity::ERROR, $result[1]->severity);
        self::assertEquals('Access to an undefined property LanguageServer\\Test\\Unit\\Diagnostics\\PhpStan\\RunnerTest::$command.', $result[1]->message);
    }

    public function testRunnerReturnsEmptyListWhenPhpStanDoesNotOutputJson(): void
    {
        $this->command
            ->method('isRunning')
            ->willReturn(false);

        $this->command
            ->method('execute')
            ->willReturn(resolve(self::PHPSTAN_OUTPUT_WITH_CONFIG_ERRORS));

        $result = null;

        $this->subject
            ->run(new ParsedDocument('file:///tmp/foo.php', '<?php', [], [], true))
            ->then(static function (array $diagnostics) use (&$result): void {
                $result = $diagnostics;
            });

        self::assertIsArray($result);
        self::assertEmpty($result);
    }
}
