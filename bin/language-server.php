<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use LanguageServer\RPC\RpcServer;
use React\EventLoop\Factory;
use React\Socket\Server;

require_once __DIR__.'/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions('src/services.php');
$container = $containerBuilder->build();

$loop = Factory::create();
$socket = new Server('127.0.0.1:8080', $loop);
$rpcServer = $container->get(RpcServer::class);
$rpcServer->listen($socket);

$loop->run();
