#!/usr/bin/env php
<?php

use Composer\XdebugHandler\XdebugHandler;
use DI\ContainerBuilder;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

(new XdebugHandler('PHPLS'))->check();

$container = (new ContainerBuilder())
    ->addDefinitions(__DIR__ . '/../src/services.php')
    ->build();

$container->get(Application::class)->run();
