<?php
/**
 * Generate a code coverage report
 *
 * @package        SimpleTest
 * @subpackage     Extensions
 */
# optional arguments:
#  --reportDir=some/directory    the default is ./coverage-report
#  --title='My Coverage Report'  title the main page of your report

/**#@+
 * include coverage files
 */
require_once(dirname(__FILE__) . '/../coverage_utils.php');
require_once(dirname(__FILE__) . '/../coverage.php');
require_once(dirname(__FILE__) . '/../coverage_reporter.php');
/**#@-*/
$cc = CodeCoverage::getInstance();
$cc->readSettings();
$handler = new CoverageDataHandler($cc->log);
$report = new CoverageReporter();
$args = CoverageUtils::parseArguments($_SERVER['argv']);
$report->reportDir = CoverageUtils::issetOr($args['reportDir'], 'coverage-report');
$report->title = CoverageUtils::issetOr($args['title'], "Simpletest Coverage");
$report->coverage = $handler->read();
$report->untouched = $handler->readUntouchedFiles();
$report->generate();
?>