<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OCP\ISession;
use OCP\ITempManager;
use OCP\Server;
use OCP\Util;

/**
 * Tests for server check functions
 *
 * @group DB
 */
class UtilCheckServerTest extends \Test\TestCase {
	private $datadir;

	/**
	 * @param array $systemOptions
	 * @return \OC\SystemConfig | \PHPUnit\Framework\MockObject\MockObject
	 */
	protected function getConfig($systemOptions) {
		$systemOptions['datadirectory'] = $this->datadir;
		$systemOptions['appstoreenabled'] = false; //it's likely that there is no app folder we can write in
		$config = $this->getMockBuilder('\OC\SystemConfig')
			->disableOriginalConstructor()
			->getMock();

		$config->expects($this->any())
			->method('getValue')
			->willReturnCallback(function ($key, $default) use ($systemOptions) {
				return $systemOptions[$key] ?? $default;
			});
		return $config;
	}

	protected function setUp(): void {
		parent::setUp();

		$this->datadir = Server::get(ITempManager::class)->getTemporaryFolder();

		file_put_contents($this->datadir . '/.ncdata', '# Nextcloud data directory');
		Server::get(ISession::class)->set('checkServer_succeeded', false);
	}

	protected function tearDown(): void {
		// clean up
		@unlink($this->datadir . '/.ncdata');
		parent::tearDown();
	}

	/**
	 * Test that checkServer() returns no errors in the regular case.
	 */
	public function testCheckServer(): void {
		$result = \OC_Util::checkServer($this->getConfig([
			'installed' => true
		]));
		$this->assertEmpty($result);
	}

	/**
	 * Test that checkServer() does not check the data dir validity
	 * when the server is not installed yet (else the setup cannot
	 * be run...)
	 */
	public function testCheckServerSkipDataDirValidityOnSetup(): void {
		// simulate old version that didn't have it
		unlink($this->datadir . '/.ncdata');

		// even though ".ncdata" is missing, the error isn't
		// triggered to allow setup to run
		$result = \OC_Util::checkServer($this->getConfig([
			'installed' => false
		]));
		$this->assertEmpty($result);
	}

	/**
	 * Test that checkServer() does not check the data dir validity
	 * when an upgrade is required (else the upgrade cannot be
	 * performed...)
	 */
	public function testCheckServerSkipDataDirValidityOnUpgrade(): void {
		// simulate old version that didn't have it
		unlink($this->datadir . '/.ncdata');

		$session = Server::get(ISession::class);
		$oldCurrentVersion = $session->get('OC_Version');

		// upgrade condition to simulate needUpgrade() === true
		$session->set('OC_Version', [6, 0, 0, 2]);

		// even though ".ncdata" is missing, the error isn't
		// triggered to allow for upgrade
		$result = \OC_Util::checkServer($this->getConfig([
			'installed' => true,
			'version' => '6.0.0.1'
		]));
		$this->assertEmpty($result);

		// restore versions
		$session->set('OC_Version', $oldCurrentVersion);
	}

	/**
	 * Test that checkDataDirectoryValidity returns no error
	 * when ".ncdata" is present.
	 */
	public function testCheckDataDirValidity(): void {
		$result = \OC_Util::checkDataDirectoryValidity($this->datadir);
		$this->assertEmpty($result);
	}

	/**
	 * Test that checkDataDirectoryValidity and checkServer
	 * both return an error when ".ncdata" is missing.
	 */
	public function testCheckDataDirValidityWhenFileMissing(): void {
		unlink($this->datadir . '/.ncdata');
		$result = \OC_Util::checkDataDirectoryValidity($this->datadir);
		$this->assertEquals(1, count($result));

		$result = \OC_Util::checkServer($this->getConfig([
			'installed' => true,
			'version' => implode('.', Util::getVersion())
		]));
		$this->assertCount(1, $result);
	}

	/**
	 * Tests that no error is given when the datadir is writable
	 */
	public function testDataDirWritable(): void {
		$result = \OC_Util::checkServer($this->getConfig([
			'installed' => true,
			'version' => implode('.', Util::getVersion())
		]));
		$this->assertEmpty($result);
	}

	/**
	 * Tests an error is given when the datadir is not writable
	 */
	public function testDataDirNotWritable(): void {
		$this->markTestSkipped('TODO: Disable because fails on drone');

		chmod($this->datadir, 0300);
		$result = \OC_Util::checkServer($this->getConfig([
			'installed' => true,
			'version' => implode('.', Util::getVersion())
		]));
		$this->assertCount(1, $result);
	}

	/**
	 * Tests no error is given when the datadir is not writable during setup
	 */
	public function testDataDirNotWritableSetup(): void {
		chmod($this->datadir, 0300);
		$result = \OC_Util::checkServer($this->getConfig([
			'installed' => false,
			'version' => implode('.', Util::getVersion())
		]));
		chmod($this->datadir, 0700); //needed for cleanup
		$this->assertEmpty($result);
	}
}
