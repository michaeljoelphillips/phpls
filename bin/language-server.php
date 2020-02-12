<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use LanguageServer\Server\Server as LSPServer;
use React\EventLoop\LoopInterface;
use React\Stream\DuplexStreamInterface;
use React\Socket\Server;

require_once __DIR__.'/../vendor/autoload.php';

$container = (new ContainerBuilder())
    ->addDefinitions(__DIR__.'/../src/services.php')
    ->build();

$server = $container->get(LSPServer::class);
$loop = $container->get(LoopInterface::class);

$socket = new Server(sprintf('127.0.0.1:%d', $argv[1]), $loop);

$server->listen($socket);

$loop->run();
