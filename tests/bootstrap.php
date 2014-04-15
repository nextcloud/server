<?php


define('PHPUNIT_RUN', 1);

$configDir = getenv('CONFIG_DIR');
if ($configDir) {
	define('PHPUNIT_CONFIG_DIR', $configDir);
}

require_once __DIR__ . '/../lib/base.php';

if (!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

OC_Hook::clear();
OC_Log::$enabled = false;
OC_FileProxy::clearProxies();
