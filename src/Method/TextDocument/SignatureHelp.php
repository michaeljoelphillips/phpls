<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\CursorPosition;
use LanguageServer\Parser\DocumentParser;
use LanguageServer\Parser\ParsedDocument;
use LanguageServer\Server\MessageHandler;
use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\ResponseMessage;
use LanguageServer\TextDocumentRegistry;
use LanguageServer\TypeResolver;
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
use PhpParser\NodeAbstract;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use function array_column;
use function array_filter;
use function array_map;
use function array_merge;
use function array_search;
use function array_values;
use function implode;
use function usort;

class SignatureHelp implements MessageHandler
{
    private const METHOD_NAME = 'textDocument/signatureHelp';

    private ClassReflector $classReflector;
    private FunctionReflector $functionReflector;
    private DocumentParser $parser;
    private TypeResolver $resolver;
    private TextDocumentRegistry $registry;

    public function __construct(ClassReflector $classReflector, FunctionReflector $functionReflector, DocumentParser $parser, TypeResolver $resolver, TextDocumentRegistry $registry)
    {
        $this->classReflector    = $classReflector;
        $this->functionReflector = $functionReflector;
        $this->parser            = $parser;
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
        $document       = $this->registry->get($params['textDocument']['uri']);
        $parsedDocument = $this->parser->parse($document);

        $cursorPosition = $document->getCursorPosition(
            $params['position']['line'] + 1,
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

        return $this->findInnermostMethodUnderCursor($methodCallNodes, $cursorPosition);
    }

    /**
     * @return NodeAbstract[]
     */
    private function findMethodCallsNearCursor(ParsedDocument $document, CursorPosition $cursorPosition) : array
    {
        $surroundingNodes = $document->getNodesAtCursor($cursorPosition);
        $methodNodes      = array_filter($surroundingNodes, [$this, 'hasSignature']);

        $this->sortNodesByDistanceFromCursor($methodNodes, $cursorPosition);

        // Reset the array indices
        return array_values($methodNodes);
    }

    /**
     * @param NodeAbstract[] $nodes
     *
     * @return NodeAbstract[]
     */
    private function sortNodesByDistanceFromCursor(array &$nodes, CursorPosition $cursorPosition) : array
    {
        usort($nodes, static function (NodeAbstract $a, NodeAbstract $b) use ($cursorPosition) {
            $distanceFromA = $cursorPosition->getRelativePosition() - $a->getStartFilePos();
            $distanceFromB = $cursorPosition->getRelativePosition() - $b->getStartFilePos();

            return $distanceFromA <=> $distanceFromB;
        });

        return $nodes;
    }

    private function hasSignature(NodeAbstract $node) : bool
    {
        return $node instanceof MethodCall
            || $node instanceof StaticCall
            || $node instanceof New_
            || $node instanceof FuncCall;
    }

    /**
     * @param NodeAbstract[] $methodCallNodes
     */
    private function findInnermostMethodUnderCursor(array $methodCallNodes, CursorPosition $cursorPosition) : NodeAbstract
    {
        $closestMethodCall    = $methodCallNodes[0];
        $argumentNodes        = array_merge(...array_column($methodCallNodes, 'args'));
        $argumentsUnderCursor = array_values(array_filter($argumentNodes, [$cursorPosition, 'contains']));

        if (empty($argumentsUnderCursor)) {
            return $closestMethodCall;
        }

        $closestArgumentUnderCursor = $this->sortNodesByDistanceFromCursor($argumentsUnderCursor, $cursorPosition)[0];

        if ($this->hasSignature($closestArgumentUnderCursor->value) &&
            $cursorPosition->isBordering($closestArgumentUnderCursor)
        ) {
            return $this->findInnermostMethodUnderCursor([$closestArgumentUnderCursor->value], $cursorPosition);
        }

        return $this->findOwningMethodByArgument($methodCallNodes, $closestArgumentUnderCursor);
    }

    /**
     * @param NodeAbstract[] $methodCalls
     */
    private function findOwningMethodByArgument(array $methodCalls, Arg $argument) : NodeAbstract
    {
        return array_values(array_filter(
            $methodCalls,
            static function (NodeAbstract $node) use ($argument) {
                return array_search($argument, $node->args) !== false;
            }
        ))[0];
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
                    $label .= (string) $param->getType() . ' ';
                }

                $label .= '$' . (string) $param->getName();

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
        $activeParameter = $this->getActiveParameterFromCursorPosition($expression, $cursorPosition);

        if ($activeParameter === null) {
            return 0;
        }

        $activeParameterPosition  = array_search($activeParameter, $expression->args);
        $maximumParameterPosition = $method->getNumberOfParameters();

        if ($activeParameterPosition <= $maximumParameterPosition) {
            return $activeParameterPosition;
        }

        return $maximumParameterPosition;
    }

    private function getActiveParameterFromCursorPosition(Expr $expression, CursorPosition $cursorPosition) : ?Arg
    {
        foreach ($expression->args as $argument) {
            if ($cursorPosition->isWithin($argument) || $cursorPosition->isSurrounding($argument)) {
                return $argument;
            }
        }

        return null;
    }
}
