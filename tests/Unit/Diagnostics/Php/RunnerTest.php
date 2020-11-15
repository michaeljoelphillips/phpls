<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Diagnostics\Php;

use LanguageServer\Diagnostics\Php\Runner;
use LanguageServer\ParsedDocument;
use LanguageServerProtocol\Diagnostic;
use PhpParser\Error;
use PHPUnit\Framework\TestCase;

class RunnerTest extends TestCase
{
    public function testRunReturnsErrorsFromParsedDocument(): void
    {
        $subject  = new Runner();
        $error    = new Error('Test Error', ['startLine' => 2, 'endLine' => 3]);
        $document = new ParsedDocument('file:///tmp/foo', '<?php', [], [$error]);

        $result = [];

        $subject
            ->run($document)
            ->then(static function (array $diagnostics) use (&$result): void {
                $result = $diagnostics;
            });

        self::assertCount(1, $result);
        self::assertContainsOnlyInstancesOf(Diagnostic::class, $result);
        self::assertEquals('PHP', $result[0]->source);
        self::assertEquals('Test Error', $result[0]->message);
        self::assertEquals(1, $result[0]->range->start->line);
        self::assertEquals(2, $result[0]->range->end->line);
    }

    public function testGetDiagnosticName(): void
    {
        $subject = new Runner();

        self::assertEquals('PHP', $subject->getName());
    }
}
