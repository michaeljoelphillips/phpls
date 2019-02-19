<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

try {
    $loop = React\EventLoop\Factory::create();

    $socket = new React\Socket\Server('127.0.0.1:8080', $loop);
    /* $input = new React\Stream\ReadableResourceStream(STDIN, $loop); */
    /* $output = new React\Stream\WritableResourceStream(STDOUT, $loop); */
    $server = new LanguageServer\RPC\TcpServer($socket);
    /* $server = new LanguageServer\RPC\StdioServer($input, $output); */
    $initialize = new LanguageServer\LSP\Command\Initialize($server);
    $signatureHelp = new LanguageServer\LSP\Command\SignatureHelp($server);

    $loop->run();
} catch (\Exception $t) {
    file_put_contents('output', $t->getMessage());
}
