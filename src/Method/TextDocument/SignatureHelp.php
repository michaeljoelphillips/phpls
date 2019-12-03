<?php

declare(strict_types=1);

namespace LanguageServer\Method\TextDocument;

use LanguageServer\CursorPosition;
use LanguageServer\Method\RequestHandlerInterface;
use LanguageServer\Parser\DocumentParserInterface;
use LanguageServer\Parser\ParsedDocument;
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

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class SignatureHelp implements RequestHandlerInterface
{
    private ClassReflector $classReflector;
    private FunctionReflector $functionReflector;
    private DocumentParserInterface $parser;
    private TypeResolver $resolver;
    private TextDocumentRegistry $registry;

    public function __construct(ClassReflector $classReflector, FunctionReflector $functionReflector, DocumentParserInterface $parser, TypeResolver $resolver, TextDocumentRegistry $registry)
    {
        $this->classReflector = $classReflector;
        $this->functionReflector = $functionReflector;
        $this->parser = $parser;
        $this->resolver = $resolver;
        $this->registry = $registry;
    }

    public function __invoke(Message $request)
    {
        return new ResponseMessage($request, $this->getSignatureHelpResponse($request->params));
    }

    private function getSignatureHelpResponse($params)
    {
        $document = $this->registry->get($params['textDocument']['uri']);
        $parsedDocument = $this->parser->parse($document);

        $cursorPosition = $document->getCursorPosition(
            $params['position']['line'] + 1,
            $params['position']['character']
        );

        $expression = $this->findExpressionAtCursor($parsedDocument, $cursorPosition);

        if (null === $expression) {
            return $this->emptySignatureHelpResponse();
        }

        try {
            $method = $this->reflectMethodFromExpression($parsedDocument, $expression);
            $signatures = $this->getSignatureHelpForMethod($method, $expression, $cursorPosition);

            return $signatures;
        } catch (OutOfBoundsException $e) {
            return $this->emptySignatureHelpResponse();
        }
    }

    private function findExpressionAtCursor(ParsedDocument $document, CursorPosition $cursorPosition): ?Expr
    {
        $methodCallNodes = $this->findMethodCallsNearCursor($document, $cursorPosition);

        if (empty($methodCallNodes)) {
            return null;
        }

        return $this->findInnermostMethodUnderCursor($methodCallNodes, $cursorPosition);
    }

    private function findMethodCallsNearCursor(ParsedDocument $document, CursorPosition $cursorPosition): array
    {
        $surroundingNodes = $document->getNodesAtCursor($cursorPosition);
        $methodNodes = array_filter($surroundingNodes, [$this, 'hasSignature']);

        $this->sortNodesByDistanceFromCursor($methodNodes, $cursorPosition);

        // Reset the array indices
        return array_values($methodNodes);
    }

    private function sortNodesByDistanceFromCursor(array &$nodes, CursorPosition $cursorPosition): array
    {
        usort($nodes, function (NodeAbstract $a, NodeAbstract $b) use ($cursorPosition) {
            $distanceFromA = $cursorPosition->getRelativePosition() - $a->getStartFilePos();
            $distanceFromB = $cursorPosition->getRelativePosition() - $b->getStartFilePos();

            return $distanceFromA <=> $distanceFromB;
        });

        return $nodes;
    }

    private function hasSignature(NodeAbstract $node): bool
    {
        return $node instanceof MethodCall
            || $node instanceof StaticCall
            || $node instanceof New_
            || $node instanceof FuncCall;
    }

    private function findInnermostMethodUnderCursor(array $methodCallNodes, CursorPosition $cursorPosition): Expr
    {
        $closestMethodCall = $methodCallNodes[0];
        $argumentNodes = array_merge(...array_column($methodCallNodes, 'args'));
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

        $methodForClosestArgument = $this->findOwningMethodByArgument($methodCallNodes, $closestArgumentUnderCursor);

        return $methodForClosestArgument;
    }

    private function findOwningMethodByArgument(array $methodCalls, Arg $argument): NodeAbstract
    {
        return array_values(array_filter(
            $methodCalls,
            function (NodeAbstract $node) use ($argument) {
                return false !== array_search($argument, $node->args);
            }
        ))[0];
    }

    private function emptySignatureHelpResponse(): SignatureHelpResponse
    {
        return new SignatureHelpResponse();
    }

    private function reflectMethodFromExpression(ParsedDocument $document, Expr $expression): ReflectionFunctionAbstract
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

    private function getSignatureHelpForMethod(ReflectionFunctionAbstract $method, Expr $expression, CursorPosition $cursorPosition): SignatureHelpResponse
    {
        $parameters = $this->extractParameterInfoFromMethod($method);
        $signatureLabel = $this->createSignatureLabel($parameters);
        $signatureInformation = new SignatureInformation($signatureLabel, $parameters, $method->getDocComment());

        $activeParameterPosition = $this->getActiveParameterPosition($method, $expression, $cursorPosition);

        return new SignatureHelpResponse([$signatureInformation], 0, $activeParameterPosition);
    }

    private function extractParameterInfoFromMethod(ReflectionFunctionAbstract $method): array
    {
        return array_map(
            function (ReflectionParameter $param) {
                $label = '';

                if (null !== $param->getType()) {
                    $label .= (string) $param->getType().' ';
                }

                $label .= '$'.(string) $param->getName();

                return new ParameterInformation($label, null);
            },
            $method->getParameters()
        );
    }

    private function createSignatureLabel(array $parameters): string
    {
        $parameterLabels = array_map(
            function (ParameterInformation $parameter) {
                return $parameter->label;
            },
            $parameters
        );

        return implode(', ', $parameterLabels);
    }

    private function getActiveParameterPosition(ReflectionFunctionAbstract $method, Expr $expression, CursorPosition $cursorPosition)
    {
        $activeParameter = $this->getActiveParameterFromCursorPosition($expression, $cursorPosition);

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

    private function getActiveParameterFromCursorPosition(Expr $expression, CursorPosition $cursorPosition): ?Arg
    {
        foreach ($expression->args as $argument) {
            if ($cursorPosition->isWithin($argument) || $cursorPosition->isSurrounding($argument)) {
                return $argument;
            }
        }

        return null;
    }
}
