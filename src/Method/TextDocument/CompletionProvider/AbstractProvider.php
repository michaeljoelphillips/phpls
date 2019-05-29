<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument\CompletionProvider;

use LanguageServer\Parser\ParsedDocument;
use LanguageServer\TypeResolver;
use PhpParser\Node\Expr;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
abstract class AbstractProvider implements CompletionProviderInterface
{
    private $resolver;
    private $reflector;

    public function __construct(TypeResolver $resolver, Reflector $reflector)
    {
        $this->resolver = $resolver;
        $this->reflector = $reflector;
    }

    public function complete(ParsedDocument $document, Expr $expression): array
    {
        $type = $this->resolver->getType($document, $expression->class);

        if (null === $type) {
            return [];
        }

        $reflection = $this->reflector->reflect($type);

        $values = $this->mapCompletionItems($document, $reflection);

        return $values;
    }

    abstract protected function mapCompletionItems(ParsedDocument $document, ReflectionClass $reflection): array;
}
