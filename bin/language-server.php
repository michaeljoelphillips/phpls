<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

try {
    $loop = React\EventLoop\Factory::create();
    $reflection = new Roave\BetterReflection\BetterReflection();
    $phpParser = (new PhpParser\ParserFactory())->create(
        PhpParser\ParserFactory::ONLY_PHP7,
        new PhpParser\Lexer([
            'usedAttributes' => [
                'comments',
                'startLine',
                'endLine',
                'startFilePos',
                'endFilePos',
            ],
        ])
    );

    $memoParser = new Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser($phpParser);
    $astLocator = new Roave\BetterReflection\SourceLocator\Ast\Locator($memoParser);

    $parser = new LanguageServer\LSP\DocumentParser($memoParser);
    $resolver = new LanguageServer\LSP\TypeResolver();
    $registry = new LanguageServer\LSP\TextDocumentRegistry();
    $reflector = new Roave\BetterReflection\Reflector\ClassReflector(
        new Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator([
            new LanguageServer\LSP\SourceLocator\RegistrySourceLocator($astLocator, $registry),
            new Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator(
                [
                    '/home/nomad/Code/answer-automation/src/',
                ],
                $astLocator
            ),
        ])
    );

    $socket = new React\Socket\Server('127.0.0.1:8080', $loop);
    $server = new LanguageServer\RPC\TcpServer($socket);
    $initialize = new LanguageServer\LSP\Command\Initialize($server);
    $signatureHelp = new LanguageServer\LSP\Command\SignatureHelp($server, $reflector, $parser, $resolver, $registry);
    $didOpen = new LanguageServer\LSP\Command\DidOpen($server, $registry);
    $didChange = new LanguageServer\LSP\Command\DidChange($server, $registry);

    $loop->run();
} catch (\Throwable | \Error $t) {
    file_put_contents('output', $t->getMessage());
}
