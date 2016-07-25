<?php
define('PHPUNIT_RUN', 1);

$configDir = getenv('CONFIG_DIR');
if ($configDir) {
	define('PHPUNIT_CONFIG_DIR', $configDir);
}

require_once __DIR__ . '/../lib/base.php';

\OC::$loader->addValidRoot(OC::$SERVERROOT . '/tests');

// especially with code coverage it will require some more time
set_time_limit(0);

// load all enabled apps
\OC_App::loadApps();

if (!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

OC_Hook::clear();
