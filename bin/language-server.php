<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Symfony\Component\Console\Application;

require_once __DIR__.'/../vendor/autoload.php';

$container = (new ContainerBuilder())
    ->addDefinitions(__DIR__.'/../src/services.php')
    ->build();

($container->get(Application::class))->run();

