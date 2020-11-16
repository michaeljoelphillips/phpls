<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Diagnostics;

use LanguageServer\Diagnostics\DiagnosticService;
use LanguageServer\Diagnostics\Runner;
use LanguageServer\ParsedDocument;
use LanguageServer\Server\Protocol\NotificationMessage;
use LanguageServer\TextDocumentRegistry;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use PHPUnit\Framework\TestCase;

use function React\Promise\reject;
use function React\Promise\resolve;

class DiagnosticServiceTest extends TestCase
{
    public function testServiceObservesTextDocumentRegistry(): void
    {
        $registry = $this->createMock(TextDocumentRegistry::class);

        $registry
            ->expects($this->once())
            ->method('on');

        new DiagnosticService($registry, []);
    }

    public function testDiagnoseEmitsPublishDiagnosticsNotification(): void
    {
        $runner   = $this->createMock(Runner::class);
        $registry = $this->createMock(TextDocumentRegistry::class);
        $document = new ParsedDocument('file://tmp/foo', '<?php', []);
        $subject  = new DiagnosticService($registry, [], $runner);

        $diagnostics = [
            new Diagnostic('Test Diagnostic', new Range(
                new Position(0, 0),
                new Position(0, -1)
            )),
        ];

        $runner
            ->method('getName')
            ->willReturn('TestDiagnostics');

        $runner
            ->method('run')
            ->with($document)
            ->willReturn(resolve($diagnostics));

        $subject->on('notification', static function (NotificationMessage $notification) use ($diagnostics): void {
            self::assertEquals('textDocument/publishDiagnostics', $notification->method);
            self::assertEquals($diagnostics, $notification->params);
        });

        $subject->diagnose($document);
    }

    public function testDiagnoseEmitsNotificationWithAggregatedDiagnostics(): void
    {
        $runnerA = $this->createMock(Runner::class);
        $runnerB = $this->createMock(Runner::class);
        $subject = new DiagnosticService($this->createMock(TextDocumentRegistry::class), [], $runnerA, $runnerB);

        $runnerADiagnostic = new Diagnostic('Test Diagnostic', new Range(new Position(0, 0), new Position(0, -1)), 1, 1, 'Runner A');
        $runnerBDiagnostic = new Diagnostic('Test Diagnostic', new Range(new Position(0, 0), new Position(0, -1)), 1, 1, 'Runner B');

        $runnerA
            ->method('getName')
            ->willReturn('Runner A');

        $runnerA
            ->method('run')
            ->willReturn(resolve([$runnerADiagnostic]));

        $runnerB
            ->method('getName')
            ->willReturn('Runner B');

        $runnerB
            ->method('run')
            ->willReturn(resolve([$runnerBDiagnostic]));

        $subject->on('notification', static function (NotificationMessage $notification) use ($runnerADiagnostic, $runnerBDiagnostic): void {
            // Only make assertions on the second version of the notification,
            // as the first doesn't contain all the diagnostics we need.

            static $notificationNumber = 1;

            if ($notificationNumber !== 2) {
                $notificationNumber++;

                return;
            }

            self::assertEquals('textDocument/publishDiagnostics', $notification->method);
            self::assertIsArray($notification->params);
            self::assertContains($runnerADiagnostic, $notification->params['diagnostics']);
            self::assertContains($runnerBDiagnostic, $notification->params['diagnostics']);
        });

        $subject->diagnose(new ParsedDocument('file://tmp/foo', '<?php', []));
    }

    public function testDiagnoseDoesNotEmitANotificationWhenRunnerRejectsAPromise(): void
    {
        $runner   = $this->createMock(Runner::class);
        $registry = $this->createMock(TextDocumentRegistry::class);
        $document = new ParsedDocument('file://tmp/foo', '<?php', []);
        $subject  = new DiagnosticService($registry, [], $runner);

        $runner
            ->method('run')
            ->with($document)
            ->willReturn(reject());

        $subject->on('notification', static function (NotificationMessage $notification): void {
            self::fail('Diagnose should not result in a notification when a runner rejects a promise');
        });

        $subject->diagnose($document);

        self::addToAssertionCount(1);
    }

    /**
     * @dataProvider ignoredFilesProvider
     */
    public function testDiagnoseSkipsDocumentsSpecifiedInIgnoreList(ParsedDocument $document): void
    {
        $runner   = $this->createMock(Runner::class);
        $registry = $this->createMock(TextDocumentRegistry::class);
        $subject  = new DiagnosticService($registry, ['vendor/', 'cache/'], $runner);

        $runner
            ->expects($this->never())
            ->method('run');

        $subject->on('notification', static function (): void {
            self::fail('Files listed as ignored should never be diagnosed');
        });

        $subject->diagnose($document);
    }

    /**
     * @return array<int, array<int, ParsedDocument>>
     */
    public function ignoredFilesProvider(): array
    {
        return [
            [new ParsedDocument('file:///tmp/cache/Foo.php', '<?php', [])],
            [new ParsedDocument('file:///tmp/vendor/Foo.php', '<?php', [])],
        ];
    }
}
