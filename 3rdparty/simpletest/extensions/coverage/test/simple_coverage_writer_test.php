<?php
require_once(dirname(__FILE__) . '/../../../autorun.php');

class SimpleCoverageWriterTest extends UnitTestCase {
	function skip() {
        $this->skipIf(
        		!file_exists('DB/sqlite.php'),
                'The Coverage extension needs to have PEAR installed');
    }
		
	function setUp() {
		require_once dirname(__FILE__) .'/../simple_coverage_writer.php';
		require_once dirname(__FILE__) .'/../coverage_calculator.php';
		
	}

	function testGenerateSummaryReport() {
        $writer = new SimpleCoverageWriter();
        $coverage = array('file' => array(0, 1));
        $untouched = array('missed-file');
        $calc = new CoverageCalculator();
        $variables = $calc->variables($coverage, $untouched);
        $variables['title'] = 'coverage';
        $out = fopen("php://memory", 'w');
        $writer->writeSummary($out, $variables);
        $dom = self::dom($out);
        $totalPercentCoverage = $dom->elements->xpath("//span[@class='totalPercentCoverage']");
        $this->assertEqual('50%', (string)$totalPercentCoverage[0]);

        $fileLinks = $dom->elements->xpath("//a[@class='byFileReportLink']");
        $fileLinkAttr = $fileLinks[0]->attributes();
        $this->assertEqual('file.html', $fileLinkAttr['href']);
        $this->assertEqual('file', (string)($fileLinks[0]));

        $untouchedFile = $dom->elements->xpath("//span[@class='untouchedFile']");
        $this->assertEqual('missed-file', (string)$untouchedFile[0]);
    }

    function testGenerateCoverageByFile() {
        $writer = new SimpleCoverageWriter();
        $cov = array(3 => 1, 4 => -2); // 2 comments, 1 code, 1 dead  (1-based indexes)
        $out = fopen("php://memory", 'w');
        $file = dirname(__FILE__) .'/sample/code.php';
        $calc = new CoverageCalculator();
        $variables = $calc->coverageByFileVariables($file, $cov);
        $variables['title'] = 'coverage';
        $writer->writeByFile($out, $variables);
        $dom = self::dom($out);

        $cells = $dom->elements->xpath("//table[@id='code']/tbody/tr/td/span");
        $this->assertEqual("comment code", self::getAttribute($cells[1], 'class'));
        $this->assertEqual("comment code", self::getAttribute($cells[3], 'class'));
        $this->assertEqual("covered code", self::getAttribute($cells[5], 'class'));
        $this->assertEqual("dead code", self::getAttribute($cells[7], 'class'));
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