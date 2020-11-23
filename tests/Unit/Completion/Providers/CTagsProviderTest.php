<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Completion\Providers;

use LanguageServer\Completion\Providers\CTagsProvider;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;

use function implode;
use function in_array;
use function sprintf;

class CTagsProviderTest extends TestCase
{
    private const TAGS_FIXTURE_DIR = __DIR__ . '/../../../fixtures/tags-fixture';

    private const COMPLETABLE_KINDS = [
        CompletionItemKind::CLASS_,
        CompletionItemKind::INTERFACE,
    ];

    public function testProviderDoesCannotCompleteWhenATagsFileCannotBeFound(): void
    {
        $subject = new CTagsProvider('/tmp', 3);

        $this->assertFalse($subject->supports(new Name('foo')));
        $this->assertFalse($subject->supports(new Class_('foo')));
    }

    public function testProviderCompletesWhenTagsFileCanBeFound(): void
    {
        $subject = new CTagsProvider(self::TAGS_FIXTURE_DIR, 3);

        $this->assertTrue($subject->supports(new Name('foo')));
    }

    public function testProviderDoesNotCompleteOnNamesSmallerThanTheMinimumKeywordLength(): void
    {
        $subject = new CTagsProvider(self::TAGS_FIXTURE_DIR, 3);

        $this->assertFalse($subject->supports(new Name('f')));
        $this->assertFalse($subject->supports(new Name('fo')));
        $this->assertTrue($subject->supports(new Name('foo')));
    }

    public function testComplete(): void
    {
        $subject    = new CTagsProvider(self::TAGS_FIXTURE_DIR, 3);
        $reflection = $this->createMock(ReflectionClass::class);

        $result = $subject->complete(new Name('Abstract'), $reflection);

        self::assertNotEmpty($result);
        self::assertContainsOnlyInstancesOf(CompletionItem::class, $result);
        self::assertEquals('PhpBench\\\\Benchmark\\\\Metadata\\\\Annotations', $result[0]->detail);
        self::assertEquals('AbstractArrayAnnotation', $result[0]->label);
        self::assertEquals(CompletionItemKind::CLASS_, $result[0]->kind);

        foreach ($result as $item) {
            if (in_array($item->kind, self::COMPLETABLE_KINDS) === true) {
                continue;
            }

            self::fail(sprintf(
                'Completion items may only be one of "%s", %s given',
                implode(', ', self::COMPLETABLE_KINDS),
                $item->kind
            ));
        }
    }
}
