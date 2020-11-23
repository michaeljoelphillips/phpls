<?php

declare(strict_types=1);

namespace LanguageServer\Test\Unit\Completion\Providers;

use LanguageServer\Completion\Providers\LocalVariableProvider;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\TestCase;

class LocalVariableProviderTest extends TestCase
{
    public function testProviderSupportsVariableNodes(): void
    {
        $subject = new LocalVariableProvider();

        $this->assertTrue($subject->supports(new Variable('foo')));
        $this->assertFalse($subject->supports(new PropertyFetch(new Variable('foo'), 'bar')));
    }
}
