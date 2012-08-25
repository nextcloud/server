<?php
/**
 * Close code coverage data collection, next step is to generate report
 * @package        SimpleTest
 * @subpackage     Extensions
 */
/**
 * include coverage files
 */
require_once(dirname(__FILE__) . '/../coverage.php');
$cc = CodeCoverage::getInstance();
$cc->readSettings();
$cc->writeUntouched();
?>