<?php
/**
 *	Extension for a TestDox reporter
 *	@package	SimpleTest
 *	@subpackage	Extensions
 *	@version	$Id: testdox.php 2004 2010-10-31 13:44:14Z jsweat $
 */

/**
 * 	TestDox reporter 
 *	@package	SimpleTest
 *	@subpackage	Extensions
 */
class TestDoxReporter extends SimpleReporter
{
    var $_test_case_pattern = '/^TestOf(.*)$/';

    function __construct($test_case_pattern = '/^TestOf(.*)$/') {
        parent::__construct();
        $this->_test_case_pattern = empty($test_case_pattern) ? '/^(.*)$/' : $test_case_pattern;
    }

    function paintCaseStart($test_name) {
        preg_match($this->_test_case_pattern, $test_name, $matches);
        if (!empty($matches[1])) {
            echo $matches[1] . "\n";
        } else {
            echo $test_name . "\n";
        }
    }

    function paintCaseEnd($test_name) {
        echo "\n";
    }

    function paintMethodStart($test_name) {
        if (!preg_match('/^test(.*)$/i', $test_name, $matches)) {
            return;
        }
        $test_name = $matches[1];
        $test_name = preg_replace('/([A-Z])([A-Z])/', '$1 $2', $test_name);
        echo '- ' . strtolower(preg_replace('/([a-zA-Z])([A-Z0-9])/', '$1 $2', $test_name));
    }

    function paintMethodEnd($test_name) {
        echo "\n";
    }

    function paintFail($message) {
        echo " [FAILED]";
    }
}
?>
