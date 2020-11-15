<?php

declare(strict_types=1);

namespace LanguageServer\Unit\Diagnostics\PhpCs;

use LanguageServer\Diagnostics\Command;
use LanguageServer\Diagnostics\PhpCs\Runner;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\DiagnosticSeverity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

use function React\Promise\resolve;

class RunnerTest extends TestCase
{
    private const PHPCS_OUTPUT_NO_ERRORS = <<<'JSON'
{
    "totals": {"errors": 0, "fixable": 0, "warnings": 0},
    "files": {}
}
JSON;

    private const PHPCS_OUTPUT_WITH_ERRORS = <<<'JSON'
{
    "totals": {"errors": 2, "fixable": 2, "warnings": 0},
    "files": {
        "src/Parser/CorrectiveParser.php": {
            "errors": 2,
            "messages": [
                {
                    "column": 1,
                    "fixable": true,
                    "line": 9,
                    "message": "Type PhpParser\\ErrorHandler\\Collecting is not used in this file.",
                    "severity": 5,
                    "source": "SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse",
                    "type": "ERROR"
                },
                {
                    "column": 13,
                    "fixable": false,
                    "line": 201,
                    "message": "Class CorrectiveParser contains unused private method formatOffendingLines().",
                    "severity": 5,
                    "source": "SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod",
                    "type": "ERROR"
                }
            ],
            "warnings": 0
        }
    }
}
JSON;

    private const PHPCS_OUTPUT_CONFIG_ERROR = <<<OUTPUT
ERROR: Ruleset ~/Code/phpls/phpcs.xml.dist is not valid
    - On line 4, column 5: error parsing attribute name
    - On line 4, column 5: attributes construct error
    - On line 4, column 5: Couldn't find end of Start Tag arg line 3


    Run "phpcs --help" for usage information

OUTPUT;

    /** @var MockObject&Command */
    private $command;

    private Runner $subject;

    public function setUp(): void
    {
        $this->command = $this->createMock(Command::class);
        $this->subject = new Runner($this->command, $this->createMock(LoggerInterface::class), 'error');
    }

    public function testConstructorOnlyTakesErrorOrWarningForSeverity(): void
    {
        $command = $this->createMock(Command::class);

        $this->expectException(UnexpectedValueException::class);

        new Runner($command, $this->createMock(LoggerInterface::class), 'foo');
    }

    public function testGetDiagnosticName(): void
    {
        self::assertEquals('PHPCS', $this->subject->getName());
    }

    public function testRunnerRejectsDocumentsThatAreNotPersisted(): void
    {
        $this->command
            ->expects($this->never())
            ->method('execute');

        $this->subject->run(new ParsedDocument('file:///tmp/foo.php', '<?php', [], [], false));
    }

    public function testRunnerTerminatesRunningCommands(): void
    {
        $this->command
            ->method('isRunning')
            ->willReturn(true);

        $this->command
            ->expects($this->once())
            ->method('terminate');

        $this->command
            ->method('execute')
            ->willReturn(resolve('{}'));

        $this->subject->run(new ParsedDocument('file:///tmp/foo.php', '<?php', [], [], true));
    }

    public function testRunnerResolvesEarlyWithNoErrorsReturned(): void
    {
        $this->command
            ->method('isRunning')
            ->willReturn(false);

        $this->command
            ->method('execute')
            ->willReturn(resolve(self::PHPCS_OUTPUT_NO_ERRORS));

        $result = null;

        $this->subject
            ->run(new ParsedDocument('file:///tmp/foo.php', '<?php', [], [], true))
            ->then(static function (array $diagnostics) use (&$result): void {
                $result = $diagnostics;
            });

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testRunnerResolvesWithDiagnosticsWhenPhpCsReportsErrors(): void
    {
        $this->command
            ->method('isRunning')
            ->willReturn(false);

        $this->command
            ->method('execute')
            ->willReturn(resolve(self::PHPCS_OUTPUT_WITH_ERRORS));

        $result = null;

        $this->subject
            ->run(new ParsedDocument('file:///tmp/foo.php', '<?php', [], [], true))
            ->then(static function (array $diagnostics) use (&$result): void {
                $result = $diagnostics;
            });

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(Diagnostic::class, $result);

        self::assertEquals(5, $result[0]->code);
        self::assertEquals(8, $result[0]->range->start->line);
        self::assertEquals(0, $result[0]->range->start->character);
        self::assertEquals(8, $result[0]->range->end->line);
        self::assertEquals(DiagnosticSeverity::ERROR, $result[0]->severity);
        self::assertEquals('Type PhpParser\ErrorHandler\Collecting is not used in this file.', $result[0]->message);

        self::assertEquals(5, $result[1]->code);
        self::assertEquals(200, $result[1]->range->start->line);
        self::assertEquals(12, $result[1]->range->start->character);
        self::assertEquals(200, $result[1]->range->end->line);
        self::assertEquals(DiagnosticSeverity::ERROR, $result[1]->severity);
        self::assertEquals('Class CorrectiveParser contains unused private method formatOffendingLines().', $result[1]->message);
    }

    public function testRunnerReturnsEmptyListWhenPhpCsDoesNotOutputJson(): void
    {
        $this->command
            ->method('isRunning')
            ->willReturn(false);

        $this->command
            ->method('execute')
            ->willReturn(resolve(self::PHPCS_OUTPUT_CONFIG_ERROR));

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
