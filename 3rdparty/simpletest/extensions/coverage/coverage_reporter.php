<?php
/**
 * @package        SimpleTest
 * @subpackage     Extensions
 */
/**#@+
 * include additional coverage files
 */
require_once dirname(__FILE__) .'/coverage_calculator.php';
require_once dirname(__FILE__) .'/coverage_utils.php';
require_once dirname(__FILE__) .'/simple_coverage_writer.php';
/**#@-*/

/**
 * Take aggregated coverage data and generate reports from it using smarty
 * templates
 * @package        SimpleTest
 * @subpackage     Extensions
 */
class CoverageReporter {
    var $coverage;
    var $untouched;
    var $reportDir;
    var $title = 'Coverage';
    var $writer;
    var $calculator;

    function __construct() {
        $this->writer = new SimpleCoverageWriter();
        $this->calculator = new CoverageCalculator();
    }

    function generateSummaryReport($out) {
        $variables = $this->calculator->variables($this->coverage, $this->untouched);
        $variables['title'] = $this->title;
        $report = $this->writer->writeSummary($out, $variables);
        fwrite($out, $report);
    }

    function generate() {
        CoverageUtils::mkdir($this->reportDir);

        $index = $this->reportDir .'/index.html';
        $hnd = fopen($index, 'w');
        $this->generateSummaryReport($hnd);
        fclose($hnd);

        foreach ($this->coverage as $file => $cov) {
            $byFile = $this->reportDir .'/'. self::reportFilename($file);
            $byFileHnd = fopen($byFile, 'w');
            $this->generateCoverageByFile($byFileHnd, $file, $cov);
            fclose($byFileHnd);
        }

        echo "generated report $index\n";
    }

    function generateCoverageByFile($out, $file, $cov) {
        $variables = $this->calculator->coverageByFileVariables($file, $cov);
        $variables['title'] = $this->title .' - '. $file;
        $this->writer->writeByFile($out, $variables);
    }

    static function reportFilename($filename) {
        return preg_replace('|[/\\\\]|', '_', $filename) . '.html';
    }
}
?>