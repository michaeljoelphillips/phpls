<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

try {
    $loop = React\EventLoop\Factory::create();
    $reflection = new Roave\BetterReflection\BetterReflection();

    $parser = new LanguageServer\LSP\DocumentParser();
    $resolver = new LanguageServer\LSP\TypeResolver();
    $registry = new LanguageServer\LSP\TextDocumentRegistry();
    $reflector = new Roave\BetterReflection\Reflector\ClassReflector(
        new LanguageServer\LSP\SourceLocator\RegistrySourceLocator($reflection->astLocator(), $registry),
    );

    $socket = new React\Socket\Server('127.0.0.1:8080', $loop);
    $server = new LanguageServer\RPC\TcpServer($socket);
    $initialize = new LanguageServer\LSP\Command\Initialize($server);
    $signatureHelp = new LanguageServer\LSP\Command\SignatureHelp($server, $reflector, $parser, $resolver, $registry);
    $didOpen = new LanguageServer\LSP\Command\DidOpen($server, $registry);
    $didChange = new LanguageServer\LSP\Command\DidChange($server, $registry);

    $loop->run();
} catch (\Exception $t) {
    file_put_contents('output', $t->getMessage());
}
