<?php

declare(strict_types=1);

namespace LanguageServer\Test\Method\TextDocument\CompletionProvider;

use LanguageServer\Method\TextDocument\CompletionProvider\ClassConstantProvider;
use LanguageServer\Parser\ParsedDocument;
use phpDocumentor\Reflection\TypeResolver;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class ClassConstantProviderTest extends TestCase
{
    private $resolver;
    private $reflector;

    public function setUp(): void
    {
        $this->resolver = $this->createMock(TypeResolver::class);
        $this->reflector = $this->createMock(Reflector::class);
    }

    public function testComplete()
    {
        $subject = new ClassConstantProvider($this->resolver, $this->reflector);

        $subject->complete($this->createMock(ParsedDocument::class), $expression);
    }
}
