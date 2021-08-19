<?php

$filename = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($filename)) {
    echo 'You must first install the vendors using composer.' . PHP_EOL;
    exit(1);
}

require_once $filename;
