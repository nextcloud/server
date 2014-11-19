<?php
/**
 * Copyright (c) 2013 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Detects tests that didn't clean up properly, show a warning, then clean up after them.
 */
class TestCleanupListener implements PHPUnit_Framework_TestListener {
	private $verbosity;

	public function __construct($verbosity = 'verbose') {
		$this->verbosity = $verbosity;
	}

	public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
	}

	public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
	}

	public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
	}

	public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
	}

	public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
	}

	public function startTest(PHPUnit_Framework_Test $test) {
	}

	public function endTest(PHPUnit_Framework_Test $test, $time) {
	}

	public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
	}

	public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
		// don't clean up the test environment if a data provider finished
		if (!($suite instanceof PHPUnit_Framework_TestSuite_DataProvider)) {
			if ($this->cleanStorages() && $this->isShowSuiteWarning()) {
				printf("TestSuite '%s': Did not clean up storages\n", $suite->getName());
			}
			if ($this->cleanFileCache() && $this->isShowSuiteWarning()) {
				printf("TestSuite '%s': Did not clean up file cache\n", $suite->getName());
			}
			if ($this->cleanStrayDataFiles() && $this->isShowSuiteWarning()) {
				printf("TestSuite '%s': Did not clean up data dir\n", $suite->getName());
			}
			if ($this->cleanStrayHooks() && $this->isShowSuiteWarning()) {
				printf("TestSuite '%s': Did not clean up hooks\n", $suite->getName());
			}
			if ($this->cleanProxies() && $this->isShowSuiteWarning()) {
				printf("TestSuite '%s': Did not clean up proxies\n", $suite->getName());
			}
		}
	}

	private function isShowSuiteWarning() {
		return $this->verbosity === 'suite' || $this->verbosity === 'detail';
	}

	private function isShowDetail() {
		return $this->verbosity === 'detail';
	}

	/**
	 * @param string $dir
	 */
	private function unlinkDir($dir) {
		if ($dh = @opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if ($file === '..' || $file === '.') {
					continue;
				}
				$path = $dir . '/' . $file;
				if (is_dir($path)) {
					$this->unlinkDir($path);
				}
				else {
					@unlink($path);
				}
			}
			closedir($dh);
		}
		@rmdir($dir);
	}

	private function cleanStrayDataFiles() {
		$knownEntries = array(
			'owncloud.log' => true,
			'owncloud.db' => true,
			'.ocdata' => true,
			'..' => true,
			'.' => true
		);
		$datadir = \OC_Config::getValue('datadirectory', \OC::$SERVERROOT . '/data');

		if (\OC_Util::runningOnWindows()) {
			$mapper = new \OC\Files\Mapper($datadir);
			$mapper->removePath($datadir, true, true);
		}

		$entries = array();
		if ($dh = opendir($datadir)) {
			while (($file = readdir($dh)) !== false) {
				if (!isset($knownEntries[$file])) {
					$entries[] = $file;
				}
			}
			closedir($dh);
		}

		if (count($entries) > 0) {
			foreach ($entries as $entry) {
				$this->unlinkDir($datadir . '/' . $entry);
				if ($this->isShowDetail()) {
					printf("Stray datadir entry: %s\n", $entry);
				}
			}
			return true;
		}

		return false;
	}

	private function cleanStorages() {
		$sql = 'DELETE FROM `*PREFIX*storages`';
		$query = \OC_DB::prepare( $sql );
		$result = $query->execute();
		if ($result > 0) {
			return true;
		}
		return false;
	}

	private function cleanFileCache() {
		$sql = 'DELETE FROM `*PREFIX*filecache`';
		$query = \OC_DB::prepare( $sql );
		$result = $query->execute();
		if ($result > 0) {
			return true;
		}
		return false;
	}

	private function cleanStrayHooks() {
		$hasHooks = false;
		$hooks = OC_Hook::getHooks();
		if (!$hooks || sizeof($hooks) === 0) {
			return false;
		}

		foreach ($hooks as $signalClass => $signals) {
			if (sizeof($signals)) {
				foreach ($signals as $signalName => $handlers ) {
					if (sizeof($handlers) > 0) {
						$hasHooks = true;
						OC_Hook::clear($signalClass, $signalName);
						if ($this->isShowDetail()) {
							printf("Stray hook: \"%s\" \"%s\"\n", $signalClass, $signalName);
						}
					}
				}
			}
		}
		return $hasHooks;
	}

	private function cleanProxies() {
		$proxies = OC_FileProxy::getProxies();
		OC_FileProxy::clearProxies();
		return count($proxies) > 0;
	}
}
