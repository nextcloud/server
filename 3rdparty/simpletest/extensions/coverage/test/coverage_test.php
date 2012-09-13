<?php
require_once(dirname(__FILE__) . '/../../../autorun.php');
require_once(dirname(__FILE__) . '/../../../mock_objects.php');

class CodeCoverageTest extends UnitTestCase {
    function skip() {
        $this->skipIf(
        		!file_exists('DB/sqlite.php'),
                'The Coverage extension needs to have PEAR installed');
    }
	
	function setUp() {
        require_once dirname(__FILE__) .'/../coverage.php';
    }
	
    function testIsFileIncluded() {
        $coverage = new CodeCoverage();
        $this->assertTrue($coverage->isFileIncluded('aaa'));
        $coverage->includes = array('a');
        $this->assertTrue($coverage->isFileIncluded('aaa'));
        $coverage->includes = array('x');
        $this->assertFalse($coverage->isFileIncluded('aaa'));
        $coverage->excludes = array('aa');
        $this->assertFalse($coverage->isFileIncluded('aaa'));
    }

    function testIsFileIncludedRegexp() {
        $coverage = new CodeCoverage();
        $coverage->includes = array('modules/.*\.php$');
        $coverage->excludes = array('bad-bunny.php');
        $this->assertFalse($coverage->isFileIncluded('modules/a.test'));
        $this->assertFalse($coverage->isFileIncluded('modules/bad-bunny.test'));
        $this->assertTrue($coverage->isFileIncluded('modules/test.php'));
        $this->assertFalse($coverage->isFileIncluded('module-bad/good-bunny.php'));
        $this->assertTrue($coverage->isFileIncluded('modules/good-bunny.php'));
    }

    function testIsDirectoryIncludedPastMaxDepth() {
        $coverage = new CodeCoverage();
        $coverage->maxDirectoryDepth = 5;
        $this->assertTrue($coverage->isDirectoryIncluded('aaa', 1));
        $this->assertFalse($coverage->isDirectoryIncluded('aaa', 5));
    }

    function testIsDirectoryIncluded() {
        $coverage = new CodeCoverage();
        $this->assertTrue($coverage->isDirectoryIncluded('aaa', 0));
        $coverage->excludes = array('b$');
        $this->assertTrue($coverage->isDirectoryIncluded('aaa', 0));
        $coverage->includes = array('a$'); // includes are ignore, all dirs are included unless excluded
        $this->assertTrue($coverage->isDirectoryIncluded('aaa', 0));
        $coverage->excludes = array('.*a$');
        $this->assertFalse($coverage->isDirectoryIncluded('aaa', 0));
    }

    function testFilter() {
        $coverage = new CodeCoverage();
        $data = array('a' => 0, 'b' => 0, 'c' => 0);
        $coverage->includes = array('b');
        $coverage->filter($data);
        $this->assertEqual(array('b' => 0), $data);
    }

    function testUntouchedFiles() {
        $coverage = new CodeCoverage();
        $touched = array_flip(array("test/coverage_test.php"));
        $actual = array();
        $coverage->includes = array('coverage_test\.php$');
        $parentDir = realpath(dirname(__FILE__));
        $coverage->getUntouchedFiles($actual, $touched, $parentDir, $parentDir);
        $this->assertEqual(array("coverage_test.php"), $actual);
    }

    function testResetLog() {
        $coverage = new CodeCoverage();
        $coverage->log = tempnam(NULL, 'php.xdebug.coverage.test.');
        $coverage->resetLog();
        $this->assertTrue(file_exists($coverage->log));
    }

    function testSettingsSerialization() {
        $coverage = new CodeCoverage();
        $coverage->log = '/banana/boat';
        $coverage->includes = array('apple', 'orange');
        $coverage->excludes = array('tomato', 'pea');
        $data = $coverage->getSettings();
        $this->assertNotNull($data);

        $actual = new CodeCoverage();
        $actual->setSettings($data);
        $this->assertEqual('/banana/boat', $actual->log);
        $this->assertEqual(array('apple', 'orange'), $actual->includes);
        $this->assertEqual(array('tomato', 'pea'), $actual->excludes);
    }

    function testSettingsCanBeReadWrittenToDisk() {
        $settings_file = 'banana-boat-coverage-settings-test.dat';
        $coverage = new CodeCoverage();
        $coverage->log = '/banana/boat';
        $coverage->settingsFile = $settings_file;
        $coverage->writeSettings();

        $actual = new CodeCoverage();
        $actual->settingsFile = $settings_file;
        $actual->readSettings();
        $this->assertEqual('/banana/boat', $actual->log);
    }
}
?>