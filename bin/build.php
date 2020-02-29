#!/usr/bin/env php
<?php

define('SRC_PATH', __DIR__.'/../src');
define('PROJECT_ROOT', __DIR__.'/..');
define('VENDOR_PATH', __DIR__.'/../vendor');
define('BUILD_PATH', __DIR__.'/../build/phpls.phar');

if (file_exists(BUILD_PATH)) {
    unlink(BUILD_PATH);
}

if (file_exists(BUILD_PATH.'.gz')) {
    unlink(BUILD_PATH.'.gz');
}

$builder = new Phar(BUILD_PATH);

$builder->buildFromDirectory(PROJECT_ROOT);

$builder->addFile('bin/language-server.php');
$builder->setStub($builder->createDefaultStub('bin/language-server.php'));
