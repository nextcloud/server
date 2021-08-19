<?php
error_reporting(E_ALL);
require_once dirname(__FILE__) . '/phpt_test.php.inc';
$phpunit = new PEAR_PHPTest(true);
require_once dirname(dirname(__FILE__)) . '/Archive/Tar.php';
