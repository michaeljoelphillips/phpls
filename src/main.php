<?php

require_once __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$input = new React\Stream\ReadableResourceStream(STDIN, $loop);
$output = new React\Stream\WritableResourceStream(STDOUT, $loop);
$server = new LanguageServer\RPC\Server($input);
$initialize = new LanguageServer\LSP\Initialize($server, $output);

$loop->run();
