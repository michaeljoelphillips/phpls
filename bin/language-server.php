<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use LanguageServer\Server\Server;
use React\EventLoop\LoopInterface;

require_once __DIR__.'/../vendor/autoload.php';

$container = (new ContainerBuilder())
    ->addDefinitions(__DIR__.'/../src/services.php')
    ->build();

$server = $container->get(Server::class);
$loop = $container->get(LoopInterface::class);

$server->start();
$loop->run();
