<?php

declare(strict_types=1);

namespace LanguageServer\Test\Parser;

use LanguageServer\Parser\IncompleteDocumentParser;
use LanguageServer\Test\FixtureTestCase;
use LanguageServer\TextDocument;
use PhpParser\Parser;

class IncompleteDocumentParserTest extends FixtureTestCase
{
    private IncompleteDocumentParser $subject;
    private Parser $parser;

    public function setUp() : void
    {
        $this->parser  = $this->createMock(Parser::class);
        $this->subject = new IncompleteDocumentParser($this->parser);
    }

    /**
     * @dataProvider incompleteSyntaxProvider
     */
    public function testParseFixesIncompleteSyntax(string $incompleteSource, string $completedSource) : void
    {
        $document = new TextDocument('file:///tmp/Foo.php', $incompleteSource, 0);

        $this
            ->parser
            ->method('parse')
            ->willReturn([]);

        $this
            ->parser
            ->expects($this->once())
            ->method('parse')
            ->with($completedSource);

        $this->subject->parse($document);
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
                    \$this->foo->stub
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

                \$foo->stub

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

                \$foo->stub

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

                \$foo->stub

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

                Foo::stub

                return;
                PHP,
            ],
            /* /1* [ *1/ */
            /* /1*     <<<PHP *1/ */
            /* /1*     <?php *1/ */

            /* /1*     \$factory->create(Factory:: *1/ */

            /* /1*     return false; *1/ */
            /* /1*     PHP *1/ */
            /* /1* ], *1/ */
            /* /1* [ *1/ */
        ];
    }
}
