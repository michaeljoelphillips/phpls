<?php

declare(strict_types=1);

namespace LanguageServer\LSP\Command;

use LanguageServer\LSP\Response\SignatureHelpResponse;
use LanguageServer\RPC\Server;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use React\Stream\WritableStreamInterface;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use stdClass;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class SignatureHelp
{
    private $finder;

    private $parser;

    public function __construct(Server $server, Parser $parser)
    {
        $this->parser = $parser;
        $this->finder = new NodeFinder();

        $server->on('textDocument/signatureHelp', [$this, 'handle']);
    }

    public function handle(object $request, WritableStreamInterface $output)
    {
        try {
            $line = $request->params->position->line + 1;
            $character = $request->params->position->character;
            $source = file_get_contents($request->params->textDocument->uri);
            $nodes = $this->parser->parse($source);
            $position = $this->cursorPosition($source, $line, $character);

            $methodCall = $this->methodAtCursor($nodes, $line, $position);
            $reflectionMethod = $this->reflectMethod($source, $methodCall, $nodes);
            $signatures = $this->formatSignatures($reflectionMethod, $methodCall);

            $result = new SignatureHelpResponse($request->id, $signatures);

            $output->write((string) $result);
        } catch (\Throwable $t) {
            var_dump($t->getMessage());
        }
    }

    /**
     * Calculate the cursor position relative to the beginning of the file.
     *
     * @param string   $code
     * @param stdClass $position
     *
     * @return int
     */
    private function cursorPosition(string $code, int $line, int $character): int
    {
        $lines = explode(PHP_EOL, $code);
        $lines = array_splice($lines, 0, $line);
        $lines[$line - 1] = substr($lines[$line - 1], 0, $character);
        $lines = implode(PHP_EOL, $lines);

        return strlen($lines);
    }

    private function methodAtCursor(array $nodes, int $line, int $position)
    {
        $methodCall = $this->finder->findFirst($nodes, function (NodeAbstract $node) use ($line, $position) {
            return $line === $node->getLine()
                && $node instanceof MethodCall
                && $node->getStartFilePos() <= $position
                && $node->getEndFilePos() >= $position;
        });

        return $methodCall;
    }

    private function reflectMethod(string $source, NodeAbstract $methodCall, array $nodes): ReflectionMethod
    {
        if ('this' === $methodCall->var->name) {
            return $this->reflectMethodFromSource($source, $methodCall->name->name);
        }

        // This only handles assignment.  Other means of method reflection:
        //
        // Methods on Instance Variables
        // Methods on objects that are results of some static function
        // Method chaining
        $variableAssignment = $this->finder->findFirst($nodes, function (NodeAbstract $node) use ($methodCall) {
            return $node instanceof Expression
                && $node->expr instanceof Assign
                && $node->expr->var->name === $methodCall->var->name;
        });

        $variableType = implode('\\', $variableAssignment->expr->expr->class->parts);

        return $this->reflectMethodFromClass($variableType, $methodCall->name->name);
    }

    private function reflectMethodFromSource(string $source, string $method)
    {
        $reflection = new BetterReflection();
        $reflector = new ClassReflector(new StringSourceLocator($source, $reflection->astLocator()));

        $reflectionClass = $reflector->getAllClasses()[0];

        return $reflectionClass->getMethod($method);
    }

    private function reflectMethodFromClass(string $class, string $method)
    {
        return (new BetterReflection())
            ->classReflector()
            ->reflect($class)
            ->getMethod($method);
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
