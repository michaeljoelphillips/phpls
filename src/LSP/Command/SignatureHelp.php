<?php

declare(strict_types=1);

namespace LanguageServer\LSP\Command;

use LanguageServer\LSP\DocumentParser;
use LanguageServer\LSP\ParsedDocument;
use LanguageServer\LSP\Response\SignatureHelpResponse;
use LanguageServer\LSP\TextDocumentRegistry;
use LanguageServer\LSP\TypeResolver;
use LanguageServer\RPC\Server;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;
use React\Stream\WritableStreamInterface;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class SignatureHelp
{
    private $resolver;

    private $reflector;

    private $parser;

    private $registry;

    private $finder;

    public function __construct(
        Server $server,
        Reflector $reflector,
        DocumentParser $parser,
        TypeResolver $resolver,
        TextDocumentRegistry $registry
    ) {
        $this->reflector = $reflector;
        $this->parser = $parser;
        $this->resolver = $resolver;
        $this->registry = $registry;
        $this->finder = new NodeFinder();

        $server->on('textDocument/signatureHelp', [$this, 'handle']);
    }

    public function handle(object $request, WritableStreamInterface $output)
    {
        try {
            $parsedDocument = $this->parseDocument($request);

            $nodes = $parsedDocument->getNodesAtCursor(
                $request->params->position->line + 1,
                $request->params->position->character
            );

            $methods = array_values(array_filter($nodes, [$this, 'hasSignature']));

            if (empty($methods)) {
                return;
            }

            $node = $methods[0];

            $reflection = $this->reflect($parsedDocument, $node);
            $signatures = $this->formatSignatures($reflection, $node);

            $result = new SignatureHelpResponse($request->id, $signatures);

            $output->write((string) $result);
        } catch (\Throwable $t) {
            var_dump($t->getMessage());
        }
    }

    private function hasSignature(NodeAbstract $node): bool
    {
        return $node instanceof MethodCall
            || $node instanceof StaticCall
            || $node instanceof New_;
    }

    private function parseDocument(object $request): ParsedDocument
    {
        $document = $this->registry->get($request->params->textDocument->uri);

        return $this->parser->parse($document);
    }

    private function reflect(ParsedDocument $document, NodeAbstract $node): ReflectionMethod
    {
        $type = $this->resolver->getType($document, $node);

        $reflection = $this->reflector->reflect($type);

        if ($node instanceof New_) {
            return $reflection->getConstructor();
        }

        return $reflection->getMethod($node->name->name);
    }

    private function formatSignatures(ReflectionMethod $method, NodeAbstract $methodCall): array
    {
        $numOfParameters = $method->getNumberOfParameters() - 1;

        $parameters = array_map(
            function (ReflectionParameter $param) {
                return [
                    'documentation' => null,
                    'label' => sprintf('%s $%s', (string) $param->getType(), $param->getName()),
                ];
            },
            $method->getParameters()
        );

        $label = array_map(
            function (array $params) {
                return $params['label'];
            },
            $parameters
        );

        $signatures = [
            'documentation' => $method->getDocComment(),
            'label' => implode(', ', $label),
        ];

        $signatures['parameters'] = $parameters;

        $argNum = 0;
        foreach ($methodCall->args as $arg) {
            if ($arg->getStartFilePos() <= $position && $arg->getEndFilePos() >= $position) {
                break;
            }

            ++$argNum;
        }

        $activeParameter = $argNum <= $numOfParameters
            ? $argNum
            : $numOfParameters;

        $body = [
            'activeParameter' => $activeParameter,
            'activeSignature' => 0,
            'signatures' => [$signatures],
        ];

        return $body;
    }
}
