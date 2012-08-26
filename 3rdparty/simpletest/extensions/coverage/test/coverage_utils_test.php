<?php
require_once dirname(__FILE__) . '/../../../autorun.php';

class CoverageUtilsTest extends UnitTestCase {
    function skip() {
        $this->skipIf(
        		!file_exists('DB/sqlite.php'),
                'The Coverage extension needs to have PEAR installed');
    }
	
	function setUp() {
    	require_once dirname(__FILE__) .'/../coverage_utils.php';
	}
	
    function testMkdir() {
        CoverageUtils::mkdir(dirname(__FILE__));
        try {
            CoverageUtils::mkdir(__FILE__);
            $this->fail("Should give error about cannot create dir of a file");
        } catch (Exception $expected) {
        }
    }

    function testIsPackageClassAvailable() {
        $coverageSource = dirname(__FILE__) .'/../coverage_calculator.php';
        $this->assertTrue(CoverageUtils::isPackageClassAvailable($coverageSource, 'CoverageCalculator'));
        $this->assertFalse(CoverageUtils::isPackageClassAvailable($coverageSource, 'BogusCoverage'));
        $this->assertFalse(CoverageUtils::isPackageClassAvailable('bogus-file', 'BogusCoverage'));
        $this->assertTrue(CoverageUtils::isPackageClassAvailable('bogus-file', 'CoverageUtils'));
    }

    function testParseArgumentsMultiValue() {
        $actual = CoverageUtils::parseArguments(array('scriptname', '--a=b', '--a=c'), True);
        $expected = array('extraArguments' => array(), 'a' => 'c', 'a[]' => array('b', 'c'));
        $this->assertEqual($expected, $actual);
    }

    function testParseArguments() {
        $actual = CoverageUtils::parseArguments(array('scriptname', '--a=b', '-c', 'xxx'));
        $expected = array('a' => 'b', 'c' => '', 'extraArguments' => array('xxx'));
        $this->assertEqual($expected, $actual);
    }

    function testParseDoubleDashNoArguments() {
        $actual = CoverageUtils::parseArguments(array('scriptname', '--aa'));
        $this->assertTrue(isset($actual['aa']));
    }

    function testParseHyphenedExtraArguments() {
        $actual = CoverageUtils::parseArguments(array('scriptname', '--alpha-beta=b', 'gamma-lambda'));
        $expected = array('alpha-beta' => 'b', 'extraArguments' => array('gamma-lambda'));
        $this->assertEqual($expected, $actual);
    }

    function testAddItemAsArray() {
        $actual = array();
        CoverageUtils::addItemAsArray($actual, 'bird', 'duck');
        $this->assertEqual(array('bird[]' => array('duck')), $actual);

        CoverageUtils::addItemAsArray(&$actual, 'bird', 'pigeon');
        $this->assertEqual(array('bird[]' => array('duck', 'pigeon')), $actual);
    }

    function testIssetOr() {
        $data = array('bird' => 'gull');
        $this->assertEqual('lab', CoverageUtils::issetOr($data['dog'], 'lab'));
        $this->assertEqual('gull', CoverageUtils::issetOr($data['bird'], 'sparrow'));
    }
}
?>