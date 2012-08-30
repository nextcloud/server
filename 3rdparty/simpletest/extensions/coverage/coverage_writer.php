<?php
/**
 * @package        SimpleTest
 * @subpackage     Extensions
 */
/**
 * @package        SimpleTest
 * @subpackage     Extensions
 */
interface CoverageWriter {

    function writeSummary($out, $variables);

    function writeByFile($out, $variables);
}
?>