<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Diagnostics;

use LanguageServer\Diagnostics\DiagnosticRunner;
use LanguageServer\Diagnostics\DiagnosticService;
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

        new DiagnosticService($registry);
    }

    public function testDiagnoseEmitsPublishDiagnosticsNotification(): void
    {
        $runner   = $this->createMock(DiagnosticRunner::class);
        $registry = $this->createMock(TextDocumentRegistry::class);
        $document = new ParsedDocument('file://tmp/foo', '<?php', []);
        $subject  = new DiagnosticService($registry, $runner);

        $diagnostics = [
            new Diagnostic('Test Diagnostic', new Range(
                new Position(0, 0),
                new Position(0, -1)
            )),
        ];

        $runner
            ->method('getDiagnosticName')
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
        $runnerA = $this->createMock(DiagnosticRunner::class);
        $runnerB = $this->createMock(DiagnosticRunner::class);
        $subject = new DiagnosticService($this->createMock(TextDocumentRegistry::class), $runnerA, $runnerB);

        $runnerADiagnostic = new Diagnostic('Test Diagnostic', new Range(new Position(0, 0), new Position(0, -1)), 1, 1, 'Runner A');
        $runnerBDiagnostic = new Diagnostic('Test Diagnostic', new Range(new Position(0, 0), new Position(0, -1)), 1, 1, 'Runner B');

        $runnerA
            ->method('getDiagnosticName')
            ->willReturn('Runner A');

        $runnerA
            ->method('run')
            ->willReturn(resolve([$runnerADiagnostic]));

        $runnerB
            ->method('getDiagnosticName')
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
        $runner   = $this->createMock(DiagnosticRunner::class);
        $registry = $this->createMock(TextDocumentRegistry::class);
        $document = new ParsedDocument('file://tmp/foo', '<?php', []);
        $subject  = new DiagnosticService($registry, $runner);

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
}
