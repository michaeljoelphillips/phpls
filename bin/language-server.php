<?php

require_once __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

/* $socket = new React\Socket\Server('127.0.0.1:8080', $loop); */
$input = new React\Stream\ReadableResourceStream(STDIN, $loop);
$output = new React\Stream\WritableResourceStream(STDOUT, $loop);
$server = LanguageServer\RPC\Server::fromStdio($input, $output);
$initialize = new LanguageServer\LSP\Initialize($server);

$loop->run();
