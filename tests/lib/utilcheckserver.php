<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Tests for server check functions
 */
class Test_Util_CheckServer extends PHPUnit_Framework_TestCase {

	private $datadir;

	public function setUp() {
		$this->datadir = \OC_Config::getValue('datadirectory', \OC::$SERVERROOT . '/data');

		file_put_contents($this->datadir . '/.ocdata', '');
	}

	public function tearDown() {
		// clean up
		@unlink($this->datadir . '/.ocdata');
	}

	/**
	 * Test that checkServer() returns no errors in the regular case.
	 */
	public function testCheckServer() {
		$result = \OC_Util::checkServer();
		$this->assertEmpty($result);
	}

	/**
	 * Test that checkServer() does not check the data dir validity
	 * when the server is not installed yet (else the setup cannot
	 * be run...)
	 */
	public function testCheckServerSkipDataDirValidityOnSetup() {
		// simulate old version that didn't have it
		unlink($this->datadir . '/.ocdata');

		$session = \OC::$server->getSession();
		$oldInstalled = \OC_Config::getValue('installed', false);

		// simulate that the server isn't setup yet
		\OC_Config::setValue('installed', false);

		// even though ".ocdata" is missing, the error isn't
		// triggered to allow setup to run
		$result = \OC_Util::checkServer();
		$this->assertEmpty($result);

		// restore config
		\OC_Config::setValue('installed', $oldInstalled);
	}

	/**
	 * Test that checkServer() does not check the data dir validity
	 * when an upgrade is required (else the upgrade cannot be
	 * performed...)
	 */
	public function testCheckServerSkipDataDirValidityOnUpgrade() {
		// simulate old version that didn't have it
		unlink($this->datadir . '/.ocdata');

		$session = \OC::$server->getSession();
		$oldCurrentVersion = $session->get('OC_Version');
		$oldInstallVersion = \OC_Config::getValue('version', '0.0.0');

		// upgrade condition to simulate needUpgrade() === true
		$session->set('OC_Version', array(6, 0, 0, 2));
		\OC_Config::setValue('version', '6.0.0.1');

		// even though ".ocdata" is missing, the error isn't
		// triggered to allow for upgrade
		$result = \OC_Util::checkServer();
		$this->assertEmpty($result);

		// restore versions
		$session->set('OC_Version', $oldCurrentVersion);
		\OC_Config::setValue('version', $oldInstallVersion);
	}

	/**
	 * Test that checkDataDirectoryValidity returns no error
	 * when ".ocdata" is present.
	 */
	public function testCheckDataDirValidity() {
		$result = \OC_Util::checkDataDirectoryValidity($this->datadir);
		$this->assertEmpty($result);
	}

	/**
	 * Test that checkDataDirectoryValidity and checkServer 
	 * both return an error when ".ocdata" is missing.
	 */
	public function testCheckDataDirValidityWhenFileMissing() {
		unlink($this->datadir . '/.ocdata');
		$result = \OC_Util::checkDataDirectoryValidity($this->datadir);
		$this->assertEquals(1, count($result));

		$result = \OC_Util::checkServer();
		$this->assertEquals(1, count($result));
	}

}
