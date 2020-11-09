<?php

declare(strict_types=1);

namespace LanguageServer\Unit\Diagnostics\PhpCs;

use LanguageServer\Diagnostics\DiagnosticCommand;
use LanguageServer\Diagnostics\PhpCs\DiagnosticRunner;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\DiagnosticSeverity;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

use function React\Promise\resolve;

class DiagnosticRunnerTest extends TestCase
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

    public function testConstructorOnlyTakesErrorOrWarningForSeverity(): void
    {
        $command = $this->createMock(DiagnosticCommand::class);

        $this->expectException(UnexpectedValueException::class);

        new DiagnosticRunner($command, 'foo');
    }

    public function testGetDiagnosticName(): void
    {
        $command = $this->createMock(DiagnosticCommand::class);
        $subject = new DiagnosticRunner($command, 'error');

        self::assertEquals('PHPCS', $subject->getDiagnosticName());
    }

    public function testRunnerRejectsDocumentsThatAreNotPersisted(): void
    {
        $command = $this->createMock(DiagnosticCommand::class);
        $subject = new DiagnosticRunner($command, 'error');

        $command
            ->expects($this->never())
            ->method('execute');

        $subject->run(new ParsedDocument('file:///tmp/foo.php', '<?php', [], [], false));
    }

    public function testRunnerTerminatesRunningCommands(): void
    {
        $command = $this->createMock(DiagnosticCommand::class);
        $subject = new DiagnosticRunner($command, 'error');

        $command
            ->method('isRunning')
            ->willReturn(true);

        $command
            ->expects($this->once())
            ->method('terminate');

        $command
            ->method('execute')
            ->willReturn(resolve('{}'));

        $subject->run(new ParsedDocument('file:///tmp/foo.php', '<?php', [], [], true));
    }

    public function testRunnerResolvesEarlyWithNoErrorsReturned(): void
    {
        $command = $this->createMock(DiagnosticCommand::class);
        $subject = new DiagnosticRunner($command, 'error');

        $command
            ->method('isRunning')
            ->willReturn(false);

        $command
            ->method('execute')
            ->willReturn(resolve(self::PHPCS_OUTPUT_NO_ERRORS));

        $result = null;

        $subject
            ->run(new ParsedDocument('file:///tmp/foo.php', '<?php', [], [], true))
            ->then(static function (array $diagnostics) use (&$result): void {
                $result = $diagnostics;
            });

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testRunnerResolvesWithDiagnosticsWhenPhpCsReportsErrors(): void
    {
        $command = $this->createMock(DiagnosticCommand::class);
        $subject = new DiagnosticRunner($command, 'error');

        $command
            ->method('isRunning')
            ->willReturn(false);

        $command
            ->method('execute')
            ->willReturn(resolve(self::PHPCS_OUTPUT_WITH_ERRORS));

        $subject
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
}
