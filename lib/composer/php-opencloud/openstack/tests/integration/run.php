<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$rootDir = dirname(dirname(__DIR__));

require_once $rootDir . '/vendor/autoload.php';

$sampleDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'samples';

$runner = new \OpenStack\Integration\Runner($sampleDir, __DIR__, 'OpenStack\\Integration');
$runner->runServices();
