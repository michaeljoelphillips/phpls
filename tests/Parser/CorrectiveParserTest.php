<?php

declare(strict_types=1);

namespace LanguageServer\Test\Parser;

use LanguageServer\Parser\CorrectiveParser;
use LanguageServer\Test\FixtureTestCase;
use PhpParser\Parser;
use Psr\Log\LoggerInterface;

class CorrectiveParserTest extends FixtureTestCase
{
    private CorrectiveParser $subject;
    private Parser $parser;

    public function setUp() : void
    {
        $this->parser  = $this->createMock(Parser::class);
        $this->subject = new CorrectiveParser($this->parser, $this->createMock(LoggerInterface::class));
    }

    /**
     * @dataProvider incompleteSyntaxProvider
     */
    public function testParseFixesIncompleteSyntax(string $incompleteSource, string $completedSource) : void
    {
        $this
            ->parser
            ->method('parse')
            ->willReturn([]);

        $this
            ->parser
            ->expects($this->once())
            ->method('parse')
            ->with($completedSource);

        $this->subject->parse($incompleteSource);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function incompleteSyntaxProvider() : array
    {
        return [
            [
                <<<PHP
                <?php

                if (true) {
                    \$this->foo->
                }
                PHP,
                <<<PHP
                <?php

                if (true) {
                    \$this->foo->stub;
                }
                PHP,
            ],
            [
                <<<PHP
                <?php

                \$foo->

                if (true) {
                    return true;
                }
                PHP,
                <<<PHP
                <?php

                \$foo->stub;

                if (true) {
                    return true;
                }
                PHP,
            ],
            [
                <<<PHP
                <?php

                \$foo->

                return \$foo;
                PHP,
                <<<PHP
                <?php

                \$foo->stub;

                return \$foo;
                PHP,
            ],
            [
                <<<PHP
                <?php

                \$foo->

                try {
                    return;
                } catch (\Throwable \$t) {
                }
                PHP,
                <<<PHP
                <?php

                \$foo->stub;

                try {
                    return;
                } catch (\Throwable \$t) {
                }
                PHP,
            ],
            [
                <<<PHP
                    \$this->->foo;
                PHP,
                <<<PHP
                    \$this->stub->foo;
                PHP,
            ],
            [
                <<<PHP
                    \$this->->foo;
                PHP,
                <<<PHP
                    \$this->stub->foo;
                PHP,
            ],
            [
                <<<PHP
                <?php

                Foo::

                return;
                PHP,
                <<<PHP
                <?php

                Foo::stub;

                return;
                PHP,
            ],
            [
                <<<PHP
                \$this->getType(\$document, \$node->class);

                \$this->

                return;
                PHP,
                <<<PHP
                \$this->getType(\$document, \$node->class);

                \$this->stub;

                return;
                PHP,
            ],
            [
                <<<PHP
                if (\$this->empty) {
                    \$this->
                }
                PHP,
                <<<PHP
                if (\$this->empty) {
                    \$this->stub;
                }
                PHP,
            ],
            [
                <<<PHP
                \$var = \$this->empty->

                return \$var;
                PHP,
                <<<PHP
                \$var = \$this->empty->stub;

                return \$var;
                PHP,
            ],
            [
                <<<PHP
                return \$node instanceof Expression
                    && \$node->expr instanceof Assign
                    && \$node->
                    && \$node->expr->var->name->name === \$property->name->name;
                PHP,
                <<<PHP
                return \$node instanceof Expression
                    && \$node->expr instanceof Assign
                    && \$node->stub
                    && \$node->expr->var->name->name === \$property->name->name;
                PHP,
            ],
            [
                <<<PHP
                return \$a-> <=> \$b->getEndFilePos();
                PHP,
                <<<PHP
                return \$a->stub <=> \$b->getEndFilePos();
                PHP,
            ],
            [
                <<<PHP
                usort(\$expressions, static function (NodeAbstract \$a, NodeAbstract \$b) {
                    return \$a->getEndFilePos() <=> \$b->getEndFilePos()
                        || \$this->
                });
                PHP,
                <<<PHP
                usort(\$expressions, static function (NodeAbstract \$a, NodeAbstract \$b) {
                    return \$a->getEndFilePos() <=> \$b->getEndFilePos()
                        || \$this->stub;
                });
                PHP,
            ],
            [
                <<<PHP
                \$this->\$list[\$parent][] = \$name;
                PHP,
                <<<PHP
                \$this->\$list[\$parent][] = \$name;
                PHP,
            ],
        ];
    }
}
