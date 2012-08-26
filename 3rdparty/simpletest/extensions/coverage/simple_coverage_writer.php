<?php
/**
 *  SimpleCoverageWriter class file
 *  @package    SimpleTest
 *  @subpackage UnitTester
 *  @version    $Id: unit_tester.php 1882 2009-07-01 14:30:05Z lastcraft $
 */
/**
 * base coverage writer class
 */
require_once dirname(__FILE__) .'/coverage_writer.php';

/**
 *  SimpleCoverageWriter class
 *  @package    SimpleTest
 *  @subpackage UnitTester
 */
class SimpleCoverageWriter implements CoverageWriter {

    function writeSummary($out, $variables) {
        extract($variables);
        $now = date("F j, Y, g:i a");
        ob_start();
        include dirname(__FILE__) . '/templates/index.php';
        $contents = ob_get_contents();
        fwrite ($out, $contents);
        ob_end_clean();
    }

    function writeByFile($out, $variables) {
        extract($variables);
        ob_start();
        include dirname(__FILE__) . '/templates/file.php';
        $contents = ob_get_contents();
        fwrite ($out, $contents);
        ob_end_clean();
    }
}
?>