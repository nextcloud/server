<?php

global $RUNTIME_NOAPPS;
$RUNTIME_NOAPPS = false;

define('PHPUNIT_RUN', 1);

require_once __DIR__.'/../lib/base.php';

if(!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

OC_Hook::clear();
OC_Log::$enabled = false;
