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

            $method = $parsedDocument->getMethodAtCursor(
                $request->params->position->line + 1,
                $request->params->position->character
            );

            $reflectionMethod = $this->reflectMethod($parsedDocument, $method);
            $signatures = $this->formatSignatures($reflectionMethod, $method);

            $result = new SignatureHelpResponse($request->id, $signatures);

            $output->write((string) $result);
        } catch (\Throwable $t) {
            var_dump($t->getMessage());
        }
    }

    private function parseDocument(object $request): ParsedDocument
    {
        $document = $this->registry->get($request->params->textDocument->uri);

        return $this->parser->parse($document);
    }

    private function reflectMethod(ParsedDocument $document, MethodCall $method): ReflectionMethod
    {
        $class = $this->resolver->getType($document, $method->var);

        $reflection = $this->reflector->reflect($class);

        return $reflection->getMethod($method->name->name);
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
