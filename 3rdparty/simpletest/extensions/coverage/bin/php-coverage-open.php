<?php
/**
 * Initialize code coverage data collection, next step is to run your tests
 * with ini setting auto_prepend_file=autocoverage.php ...
 *
 * @package        SimpleTest
 * @subpackage     Extensions
 */ 
# optional arguments:
#  --include=<some filepath regexp>      these files should be included coverage report
#  --exclude=<come filepath regexp>      these files should not be included in coverage report
#  --maxdepth=2                          when considering which file were not touched, scan directories 
#
# Example: 
# php-coverage-open.php --include='.*\.php$' --include='.*\.inc$' --exclude='.*/tests/.*' 
/**#@+
 * include coverage files
 */
require_once(dirname(__FILE__) . '/../coverage_utils.php');
CoverageUtils::requireSqlite();
require_once(dirname(__FILE__) . '/../coverage.php');
/**#@-*/
$cc = new CodeCoverage();
$cc->log = 'coverage.sqlite';
$args = CoverageUtils::parseArguments($_SERVER['argv'], TRUE);
$cc->includes = CoverageUtils::issetOr($args['include[]'], array('.*\.php$'));
$cc->excludes = CoverageUtils::issetOr($args['exclude[]']); 
$cc->maxDirectoryDepth = (int)CoverageUtils::issetOr($args['maxdepth'], '1');
$cc->resetLog();
$cc->writeSettings();
?>