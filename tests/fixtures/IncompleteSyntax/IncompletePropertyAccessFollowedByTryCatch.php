<?php

$foo = new \stdClass();

$foo->

try {
    $foo->bar();
} catch (\Throwable $t) {
}
