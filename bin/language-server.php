<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use LanguageServer\Server\Server as LanguageServer;
use React\EventLoop\Factory;
use React\Stream\CompositeStream;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;

require_once __DIR__.'/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__.'/../src/services.php');
$container = $containerBuilder->build();

$loop = Factory::create();

$stream = new CompositeStream(
    new ReadableResourceStream(STDIN, $loop),
    new WritableResourceStream(STDOUT, $loop)
);

$rpcServer = $container->get(LanguageServer::class);
$rpcServer->listen($stream);

$loop->run();
