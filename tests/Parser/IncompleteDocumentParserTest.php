<?php

declare(strict_types=1);

namespace LanguageServer\Test\Parser;

use LanguageServer\Parser\IncompleteDocumentParser;
use LanguageServer\Parser\LenientParser;
use LanguageServer\Parser\ParsedDocument;
use LanguageServer\Test\FixtureTestCase;
use LanguageServer\TextDocument;
use PhpParser\Error;
use PhpParser\Node\Expr\Error as ExprError;
use PhpParser\ParserFactory;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class IncompleteDocumentParserTest extends FixtureTestCase
{
    private $subject;

    public function setUp(): void
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        $this->subject = new IncompleteDocumentParser(new LenientParser($parser));
    }

    /**
     * @dataProvider incompleteSyntaxProvider
     */
    public function testParseFixesIncompleteSyntax(string $incompleteSource): void
    {
        $document = new TextDocument('file:///tmp/Foo.php', $incompleteSource, 0);

        $parsedDocument = $this->subject->parse($document);

        $this->assertDocumentHasNoErrors($parsedDocument);
    }

    private function assertDocumentHasNoErrors(ParsedDocument $document): void
    {
        $this->assertEmpty($document->findNodes(Error::class), 'Failed asserting that the ParsedDocument contained no errors.');
        $this->assertEmpty($document->findNodes(ExprError::class), 'Failed asserting that the ParsedDocument contained no errors.');
    }

    public function incompleteSyntaxProvider(): array
    {
        return [
            [
                <<<PHP
                <?php

                if (true) {
                    \$this->foo->
                }

                PHP
            ],
            [
                <<<PHP
                <?php

                \$foo->

                if (true) {
                    return true;
                }
                PHP
            ],
            [
                <<<PHP
                <?php

                \$foo->

                return \$foo;
                PHP
            ],
            [
                <<<PHP
                <?php

                \$foo->

                try {
                    return;
                } catch (\Throwable \$t) {
                }
                PHP
            ],
            [
                <<<PHP
                \$foo->
                \$foo->bar(\$bar->
                \$foo->bar(\$bar->foo->
                \$foo->bar(\$bar->foo->baz()->
                \$foo->bar(\$bar->foo->baz()->foo
                \$foo->bar->
                PHP
            ],
            [
                <<<PHP
                <?php

                Foo::

                return;
                PHP
            ],
            [
                <<<PHP
                <?php

                \$factory->create(Factory::

                return false;
                PHP
            ],
            [
                <<<PHP
                <?php

                return \$this->foo(\$this->);
                PHP
            ],
        ];
    }
}
