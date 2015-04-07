<?php
define('PHPUNIT_RUN', 1);

$configDir = getenv('CONFIG_DIR');
if ($configDir) {
	define('PHPUNIT_CONFIG_DIR', $configDir);
}

if (!defined('HHVM_VERSION')) {
	if(version_compare(phpversion(), '5.6.0', '>=') &&
		ini_get('always_populate_raw_post_data') !== '-1') {
		throw new Exception("'always_populate_raw_post_data' has to be set to '-1' in your php.ini");
	}
}

require_once __DIR__ . '/../lib/base.php';

// load minimum set of apps
OC_App::loadApps(array('authentication'));
OC_App::loadApps(array('filesystem', 'logging'));

if (!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

OC_Hook::clear();
OC_Log::$enabled = false;
