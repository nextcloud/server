<?php
require_once(dirname(__FILE__) . '/../../../autorun.php');

class CoverageCalculatorTest extends UnitTestCase {
    function skip() {
        $this->skipIf(
        		!file_exists('DB/sqlite.php'),
                'The Coverage extension needs to have PEAR installed');
    }
    
	function setUp() {
       	require_once dirname(__FILE__) .'/../coverage_calculator.php';
        $this->calc = new CoverageCalculator();
    }

    function testVariables() {
        $coverage = array('file' => array(1,1,1,1));
        $untouched = array('missed-file');
        $variables = $this->calc->variables($coverage, $untouched);
        $this->assertEqual(4, $variables['totalLoc']);
        $this->assertEqual(100, $variables['totalPercentCoverage']);
        $this->assertEqual(4, $variables['totalLinesOfCoverage']);
        $expected = array('file' => array('byFileReport' => 'file.html', 'percentage' => 100));
        $this->assertEqual($expected, $variables['coverageByFile']);
        $this->assertEqual(50, $variables['filesTouchedPercentage']);
        $this->assertEqual($untouched, $variables['untouched']);
    }

    function testPercentageCoverageByFile() {
        $coverage = array(0,0,0,1,1,1);
        $results = array();
        $this->calc->percentCoverageByFile($coverage, 'file', $results);
        $pct = $results[0];
        $this->assertEqual(50, $pct['file']['percentage']);
        $this->assertEqual('file.html', $pct['file']['byFileReport']);
    }

    function testTotalLoc() {
        $this->assertEqual(13, $this->calc->totalLoc(10, array(1,2,3)));
    }

    function testLineCoverage() {
        $this->assertEqual(10, $this->calc->lineCoverage(10, -1));
        $this->assertEqual(10, $this->calc->lineCoverage(10, 0));
        $this->assertEqual(11, $this->calc->lineCoverage(10, 1));
    }

    function testTotalCoverage() {
        $this->assertEqual(11, $this->calc->totalCoverage(10, array(-1,1)));
    }

    static function getAttribute($element, $attribute) {
        $a = $element->attributes();
        return $a[$attribute];
    }

    static function dom($stream) {
        rewind($stream);
        $actual = stream_get_contents($stream);
        $html = DOMDocument::loadHTML($actual);
        return simplexml_import_dom($html);
    }
}

?>