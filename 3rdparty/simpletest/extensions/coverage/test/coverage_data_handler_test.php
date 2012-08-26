<?php
require_once(dirname(__FILE__) . '/../../../autorun.php');

class CoverageDataHandlerTest extends UnitTestCase {
    function skip() {
        $this->skipIf(
        		!file_exists('DB/sqlite.php'),
                'The Coverage extension needs to have PEAR installed');
    }
    
	function setUp() {
       	require_once dirname(__FILE__) .'/../coverage_data_handler.php';
    }

    function testAggregateCoverageCode() {
        $handler = new CoverageDataHandler($this->tempdb());
        $this->assertEqual(-2, $handler->aggregateCoverageCode(-2, -2));
        $this->assertEqual(-2, $handler->aggregateCoverageCode(-2, 10));
        $this->assertEqual(-2, $handler->aggregateCoverageCode(10, -2));
        $this->assertEqual(-1, $handler->aggregateCoverageCode(-1, -1));
        $this->assertEqual(10, $handler->aggregateCoverageCode(-1, 10));
        $this->assertEqual(10, $handler->aggregateCoverageCode(10, -1));
        $this->assertEqual(20, $handler->aggregateCoverageCode(10, 10));
    }

    function testSimpleWriteRead() {
        $handler = new CoverageDataHandler($this->tempdb());
        $handler->createSchema();
        $coverage = array(10 => -2, 20 => -1, 30 => 0, 40 => 1);
        $handler->write(array('file' => $coverage));

        $actual = $handler->readFile('file');
        $expected = array(10 => -2, 20 => -1, 30 => 0, 40 => 1);
        $this->assertEqual($expected, $actual);
    }

    function testMultiFileWriteRead() {
        $handler = new CoverageDataHandler($this->tempdb());
        $handler->createSchema();
        $handler->write(array(
    	'file1' => array(-2, -1, 1), 
    	'file2' => array(-2, -1, 1)
        ));
        $handler->write(array(
    	'file1' => array(-2, -1, 1)
        ));

        $expected = array(
    	'file1' => array(-2, -1, 2),
    	'file2' => array(-2, -1, 1)
        );
        $actual = $handler->read();
        $this->assertEqual($expected, $actual);
    }

    function testGetfilenames() {
        $handler = new CoverageDataHandler($this->tempdb());
        $handler->createSchema();
        $rawCoverage = array('file0' => array(), 'file1' => array());
        $handler->write($rawCoverage);
        $actual = $handler->getFilenames();
        $this->assertEqual(array('file0', 'file1'), $actual);
    }

    function testWriteUntouchedFiles() {
        $handler = new CoverageDataHandler($this->tempdb());
        $handler->createSchema();
        $handler->writeUntouchedFile('bluejay');
        $handler->writeUntouchedFile('robin');
        $this->assertEqual(array('bluejay', 'robin'), $handler->readUntouchedFiles());
    }

    function testLtrim() {
        $this->assertEqual('ber', CoverageDataHandler::ltrim('goo', 'goober'));
        $this->assertEqual('some/file', CoverageDataHandler::ltrim('./', './some/file'));
        $this->assertEqual('/x/y/z/a/b/c', CoverageDataHandler::ltrim('/a/b/', '/x/y/z/a/b/c'));
    }

    function tempdb() {
        return tempnam(NULL, 'coverage.test.db');
    }
}
?>