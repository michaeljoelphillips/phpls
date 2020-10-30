<?php

declare(strict_types=1);

namespace LanguageServer\MessageHandler\TextDocument;

use LanguageServer\CursorPosition;
use LanguageServer\Inference\TypeResolver;
use LanguageServer\ParsedDocument;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use LanguageServer\TextDocumentRegistry;
use LanguageServerProtocol\ParameterInformation;
use LanguageServerProtocol\SignatureHelp as SignatureHelpResponse;
use LanguageServerProtocol\SignatureInformation;
use OutOfBoundsException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use function array_filter;
use function array_key_last;
use function array_map;
use function implode;

class SignatureHelp implements MessageHandler
{
    private const METHOD_NAME = 'textDocument/signatureHelp';

    private ClassReflector $classReflector;
    private FunctionReflector $functionReflector;
    private TypeResolver $resolver;
    private TextDocumentRegistry $registry;

    public function __construct(ClassReflector $classReflector, FunctionReflector $functionReflector, TypeResolver $resolver, TextDocumentRegistry $registry)
    {
        $this->classReflector    = $classReflector;
        $this->functionReflector = $functionReflector;
        $this->resolver          = $resolver;
        $this->registry          = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $request, callable $next)
    {
        if ($request->method !== self::METHOD_NAME) {
            return $next->__invoke($request);
        }

        return new ResponseMessage($request, $this->getSignatureHelpResponse($request->params));
    }

    /**
     * @param array<string, mixed> $params
     */
    private function getSignatureHelpResponse(array $params) : SignatureHelpResponse
    {
        $parsedDocument = $this->registry->get($params['textDocument']['uri']);

        $cursorPosition = $parsedDocument->getCursorPosition(
            $params['position']['line'],
            $params['position']['character']
        );

        $expression = $this->findExpressionAtCursor($parsedDocument, $cursorPosition);

        if ($expression === null) {
            return $this->emptySignatureHelpResponse();
        }

        try {
            $method = $this->reflectMethodFromExpression($parsedDocument, $expression);

            return $this->getSignatureHelpForMethod($method, $expression, $cursorPosition);
        } catch (OutOfBoundsException $e) {
            return $this->emptySignatureHelpResponse();
        }
    }

    private function findExpressionAtCursor(ParsedDocument $document, CursorPosition $cursorPosition) : ?Expr
    {
        $methodCallNodes = $this->findMethodCallsNearCursor($document, $cursorPosition);

        if (empty($methodCallNodes)) {
            return null;
        }

        return $methodCallNodes[array_key_last($methodCallNodes)];
    }

    /**
     * @return NodeAbstract[]
     */
    private function findMethodCallsNearCursor(ParsedDocument $document, CursorPosition $cursorPosition) : array
    {
        $surroundingNodes = array_filter($document->getNodesAtCursor($cursorPosition), [$this, 'hasSignature']);
        $surroundingNodes = array_map(static fn(NodeAbstract $node) => $node instanceof Expression ? $node->expr : $node, $surroundingNodes);

        return array_filter($surroundingNodes, fn (NodeAbstract $node) => $this->isCursorWithinArgumentList($node, $cursorPosition));
    }

    private function hasSignature(NodeAbstract $node) : bool
    {
        if ($node instanceof Expression) {
            return $this->hasSignature($node->expr);
        }

        return $node instanceof MethodCall
            || $node instanceof StaticCall
            || $node instanceof New_
            || $node instanceof FuncCall;
    }

    private function isCursorWithinArgumentList(NodeAbstract $node, CursorPosition $cursor) : bool
    {
        $position = $cursor->getRelativePosition();

        if (empty($node->args) === false) {
            return $node->args[0]->getStartFilePos() - 1 <= $position
                && $node->getEndFilePos() + 1 > $position;
        }

        return $node->getEndFilePos() - 1 <= $position
            && $node->getEndFilePos() + 1 > $position;
    }

    private function emptySignatureHelpResponse() : SignatureHelpResponse
    {
        return new SignatureHelpResponse();
    }

    private function reflectMethodFromExpression(ParsedDocument $document, Expr $expression) : ReflectionFunctionAbstract
    {
        if ($expression instanceof FuncCall) {
            return $this->functionReflector->reflect($expression->name->toCodeString());
        }

        $type = $this->resolver->getType($document, $expression);

        $reflection = $this->classReflector->reflect($type);

        if ($expression instanceof New_) {
            return $reflection->getConstructor();
        }

        return $reflection->getMethod($expression->name->name);
    }

    private function getSignatureHelpForMethod(ReflectionFunctionAbstract $method, Expr $expression, CursorPosition $cursorPosition) : SignatureHelpResponse
    {
        $parameters           = $this->extractParameterInfoFromMethod($method);
        $signatureLabel       = $this->createSignatureLabel($parameters);
        $signatureInformation = new SignatureInformation($signatureLabel, $parameters, $method->getDocComment());

        $activeParameterPosition = $this->getActiveParameterPosition($method, $expression, $cursorPosition);

        return new SignatureHelpResponse([$signatureInformation], 0, $activeParameterPosition);
    }

    /**
     * @return ParameterInformation[]
     */
    private function extractParameterInfoFromMethod(ReflectionFunctionAbstract $method) : array
    {
        return array_map(
            static function (ReflectionParameter $param) {
                $label = '';

                if ($param->getType() !== null) {
                    $label = $param->getType() . ' ';
                }

                $label .= '$' . $param->getName();

                return new ParameterInformation($label, null);
            },
            $method->getParameters()
        );
    }

    /**
     * @param ParameterInformation[] $parameters
     */
    private function createSignatureLabel(array $parameters) : string
    {
        $parameterLabels = array_map(
            static function (ParameterInformation $parameter) {
                return $parameter->label;
            },
            $parameters
        );

        return implode(', ', $parameterLabels);
    }

    private function getActiveParameterPosition(ReflectionFunctionAbstract $method, Expr $expression, CursorPosition $cursorPosition) : int
    {
        [$activeParameterPosition, $activeParameter] = $this->getActiveParameterFromCursorPosition($expression, $cursorPosition);

        if ($activeParameter === null) {
            return $activeParameterPosition;
        }

        $maximumParameterPosition = $method->getNumberOfParameters() - 1;

        return $activeParameterPosition > $maximumParameterPosition ? $maximumParameterPosition : $activeParameterPosition;
    }

    /**
     * @return array<int, int|Arg>
     */
    private function getActiveParameterFromCursorPosition(Expr $expression, CursorPosition $cursorPosition) : array
    {
        $position = 0;

        foreach ($expression->args as $argument) {
            if ($cursorPosition->contains($argument)) {
                return [$position, $argument];
            }

            $position++;
        }

        return [0, null];
    }
}
