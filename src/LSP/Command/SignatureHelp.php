<?php

declare(strict_types=1);

namespace LanguageServer\LSP\Command;

use LanguageServer\LSP\DocumentParser;
use LanguageServer\LSP\ParsedDocument;
use LanguageServer\LSP\TextDocumentRegistry;
use LanguageServer\LSP\TypeResolver;
use LanguageServer\RPC\JsonRpcResponse;
use LanguageServer\RPC\Server;
use LanguageServerProtocol\ParameterInformation;
use LanguageServerProtocol\SignatureHelp as SignatureHelpResponse;
use LanguageServerProtocol\SignatureInformation;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\NodeAbstract;
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

        $server->on('textDocument/signatureHelp', [$this, 'handle']);
    }

    public function handle(object $request, WritableStreamInterface $output)
    {
        try {
            $response = $this->getSignatureHelpResponse($request);

            $output->write($response->getResponse());
        } catch (\Throwable $t) {
            var_dump($t->getMessage());
        }
    }

    private function getSignatureHelpResponse(object $request): JsonRpcResponse
    {
        $parsedDocument = $this->parseDocument($request);
        $expression = $this->findExpressionAtCursor($parsedDocument, $request);
        $method = $this->reflectMethodFromExpression($parsedDocument, $expression);
        $signatures = $this->buildResponse($method, $expression);

        return new JsonRpcResponse($request->id, $signatures);
    }

    private function parseDocument(object $request): ParsedDocument
    {
        $document = $this->registry->get($request->params->textDocument->uri);

        return $this->parser->parse($document);
    }

    private function findExpressionAtCursor(ParsedDocument $document, object $request): Expr
    {
        $nodes = $document->getNodesAtCursor(
            $request->params->position->line + 1,
            $request->params->position->character
        );

        $expression = $this->filterNodesWithSignatures($nodes);

        if (empty($expression)) {
            throw new \Exception('No Nodes found.');
        }

        return $expression[0];
    }

    private function filterNodesWithSignatures(array $nodes): array
    {
        $methodNodes = array_filter($nodes, function (NodeAbstract $node) {
            return $node instanceof MethodCall
                || $node instanceof StaticCall
                || $node instanceof New_;
        });

        // Reset the array indices
        return array_values($methodNodes);
    }

    private function reflectMethodFromExpression(ParsedDocument $document, Expr $expression): ReflectionMethod
    {
        $type = $this->resolver->getType($document, $expression);

        $reflection = $this->reflector->reflect($type);

        if ($expression instanceof New_) {
            return $reflection->getConstructor();
        }

        return $reflection->getMethod($expression->name->name);
    }

    private function buildResponse(ReflectionMethod $method, Expr $expression): SignatureHelpResponse
    {
        $parameters = $this->extractParameterInfoFromMethod($method);
        $signatureInformation = new SignatureInformation('Foo', $parameters, $method->getDocComment());
        $activeParameterPosition = $this->getActiveParameterPosition($method, $expression);

        return new SignatureHelpResponse([$signatureInformation], 0, $activeParameterPosition);
    }

    private function extractParameterInfoFromMethod(ReflectionMethod $method): array
    {
        return array_map(
            function (ReflectionParameter $param) {
                $label = sprintf('%s $%s', (string) $param->getType(), (string) $param->getName());

                return new ParameterInformation($label, null);
            },
            $method->getParameters()
        );
    }

    private function getActiveParameterPosition(ReflectionMethod $method, Expr $expression)
    {
        $activeParameter = $this->getActiveParameterFromCursorPosition($expression);

        if (null === $activeParameter) {
            return 0;
        }

        $activeParameterPosition = array_search($activeParameter, $expression->args);
        $maximumParameterPosition = $method->getNumberOfParameters();

        if ($activeParameterPosition <= $maximumParameterPosition) {
            return $activeParameterPosition;
        }

        return $maximumParameterPosition;
    }

    private function getActiveParameterFromCursorPosition(Expr $expression): ?Arg
    {
        $currentCursorPosition = 0;

        foreach ($expression->args as $argument) {
            if ($this->isCursorWithinArgument($argument, $currentCursorPosition)) {
                return $argument;
            }
        }

        return null;
    }

    private function isCursorWithinArgument(Arg $node, int $cursorPosition): bool
    {
        return true;

        /* return $node->getStartFilePos() <= $cursorPosition */
        /*     && $node->getEndFilePos() >= $cursorPosition; */
    }
}
