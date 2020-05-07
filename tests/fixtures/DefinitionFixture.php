<?php

declare(strict_types=1);

namespace App;

class DefinitionFixture
{
    public function __construct()
    {
        $this->foo = new TextDocumentFixture();
        $this->bar = new NonExistentType();
    }
}
