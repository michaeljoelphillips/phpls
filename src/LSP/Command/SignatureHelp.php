<?php

declare(strict_types=1);

namespace LanguageServer\LSP\Command;

use LanguageServer\LSP\Response\SignatureHelpResponse;
use LanguageServer\RPC\Server;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;
use React\Stream\WritableStreamInterface;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class SignatureHelp
{
    private $finder;

    public function __construct(Server $server)
    {
        $this->finder = new NodeFinder();

        $server->on('textDocument/signatureHelp', [$this, 'handle']);
    }

    public function handle(object $request, WritableStreamInterface $output)
    {
        try {
            $source = file_get_contents($request->params->textDocument->uri);
            $position = $request->params->position;

            $position = $this->findCursorPosition(
                $source,
                $line = $position->line + 1,
                $position->character
            );

            $reflectionMethod = $this->reflectMethodAtCursor($source, $line, $position);


            $result = (string) new SignatureHelpResponse($request->id, $reflectionMethod);
            var_dump($result);

            $output->write($result);
        } catch (\Throwable $t) {
            var_dump($t->getMessage());
        }
    }

    private function findCursorPosition(string $code, int $line, int $character): int
    {
        $lines = explode(PHP_EOL, $code);
        $lines = array_splice($lines, 0, $line);
        $lines[$line - 1] = substr($lines[$line - 1], 0, $character);
        $lines = implode(PHP_EOL, $lines);

        return strlen($lines);
    }

    private function reflectMethodAtCursor(string $source, int $line, int $position): array
    {
        $reflection = new BetterReflection();
        $reflector = new ClassReflector(
            new AggregateSourceLocator([
                new StringSourceLocator($source, $reflection->astLocator()),
                new AutoloadSourceLocator($reflection->astLocator())
            ])
        );

        $reflectionClass = $reflector->getAllClasses()[0];

        $methodCall = $this->finder->findFirst($reflectionClass->getAst(), function (NodeAbstract $node) use ($line, $position) {
            return $line === $node->getLine()
            && $node instanceof MethodCall
            && $node->getStartFilePos() <= $position
            && $node->getEndFilePos() >= $position;
        });

        $reflectionMethod = $reflectionClass->getMethod($methodCall->name->name);
        $numOfParameters = $reflectionMethod->getNumberOfParameters() - 1;

        $parameters = array_map(
            function (ReflectionParameter $param) {
                return [
                    'documentation' => null,
                    'label' => sprintf('%s $%s', (string) $param->getType(), $param->getName()),
                ];
            },
            $reflectionMethod->getParameters()
        );

        $label = array_map(
            function (array $params) {
                return $params['label'];
            },
            $parameters
        );

        $signatures = [
            'documentation' => $reflectionMethod->getDocComment(),
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
