<?php
/**
 * @package        SimpleTest
 * @subpackage     Extensions
 */
/**
 * Include this in any file to start coverage, coverage will automatically end
 * when process dies.
 */
require_once(dirname(__FILE__) .'/coverage.php');

if (CodeCoverage::isCoverageOn()) {
    $coverage = CodeCoverage::getInstance();
    $coverage->startCoverage();
    register_shutdown_function("stop_coverage");
}

function stop_coverage() {
    # hack until i can think of a way to run tests first and w/o exiting
    $autorun = function_exists("run_local_tests");
    if ($autorun) {
        $result = run_local_tests();
    }
    CodeCoverage::getInstance()->stopCoverage();
    if ($autorun) {
        exit($result ? 0 : 1);
    }
}
?>