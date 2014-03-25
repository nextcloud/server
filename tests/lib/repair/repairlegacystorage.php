<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Tests for the converting of legacy storages to home storages.
 *
 * @see \OC\Repair\RepairLegacyStorages
 */
class TestRepairLegacyStorages extends PHPUnit_Framework_TestCase {

	private $user;
	private $repair;

	private $dataDir;
	private $oldDataDir;

	private $legacyStorageId;
	private $newStorageId;

	public function setUp() {
		$this->config = \OC::$server->getConfig();
		$this->connection = \OC_DB::getConnection();
		$this->oldDataDir = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data/');

		$this->repair = new \OC\Repair\RepairLegacyStorages($this->config, $this->connection);
	}

	public function tearDown() {
		\OC_User::deleteUser($this->user);

		$sql = 'DELETE FROM `*PREFIX*storages`';
		$this->connection->executeQuery($sql);
		$sql = 'DELETE FROM `*PREFIX*filecache`';
		$this->connection->executeQuery($sql);
		\OCP\Config::setSystemValue('datadirectory', $this->oldDataDir);
		$this->config->setAppValue('core', 'repairlegacystoragesdone', 'no');
	}

	function prepareSettings($dataDir, $userId) {
		// hard-coded string as we want a predictable fixed length
		// no data will be written there
		$this->dataDir = $dataDir;
		\OCP\Config::setSystemValue('datadirectory', $this->dataDir);

		$this->user = $userId;
		$this->legacyStorageId = 'local::' . $this->dataDir . $this->user . '/';
		$this->newStorageId = 'home::' . $this->user;
		\OC_User::createUser($this->user, $this->user);
	}

	/**
	 * Create a storage entry
	 *
	 * @param string $storageId
	 */
	private function createStorage($storageId) {
		$sql = 'INSERT INTO `*PREFIX*storages` (`id`)'
			. ' VALUES (?)';

		$storageId = \OC\Files\Cache\Storage::adjustStorageId($storageId);
		$numRows = $this->connection->executeUpdate($sql, array($storageId));
		$this->assertEquals(1, $numRows);

		return \OC_DB::insertid('*PREFIX*storages');
	}

	/**
	 * Returns the storage id based on the numeric id
	 *
	 * @param int $numericId numeric id of the storage
	 * @return string storage id or null if not found
	 */
	private function getStorageId($storageId) {
		$numericId = \OC\Files\Cache\Storage::getNumericStorageId($storageId);
		if (!is_null($numericId)) {
			return (int)$numericId;
		}
		return null;
	}

	/**
	 * Create dummy data in the filecache for the given storage numeric id
	 *
	 * @param string $storageId storage id
	 */
	private function createData($storageId) {
		$cache = new \OC\Files\Cache\Cache($storageId);
		$cache->put(
			'dummyfile.txt',
			array('size' => 5, 'mtime' => 12, 'mimetype' => 'text/plain')
		);
	}

	/**
	 * Test that existing home storages are left alone when valid.
	 * @dataProvider settingsProvider
	 */
	public function testNoopWithExistingHomeStorage($dataDir, $userId) {
		$this->prepareSettings($dataDir, $userId);
		$newStorageNumId = $this->createStorage($this->newStorageId);

		$this->repair->run();

		$this->assertNull($this->getStorageId($this->legacyStorageId));
		$this->assertEquals($newStorageNumId, $this->getStorageId($this->newStorageId));
	}

	/**
	 * Test that legacy storages are converted to home storages when
	 * the latter does not exist.
	 * @dataProvider settingsProvider
	 */
	public function testConvertLegacyToHomeStorage($dataDir, $userId) {
		$this->prepareSettings($dataDir, $userId);
		$legacyStorageNumId = $this->createStorage($this->legacyStorageId);

		$this->repair->run();

		$this->assertNull($this->getStorageId($this->legacyStorageId));
		$this->assertEquals($legacyStorageNumId, $this->getStorageId($this->newStorageId));
	}

	/**
	 * Test that legacy storages are converted to home storages
	 * when home storage already exists but has no data.
	 * @dataProvider settingsProvider
	 */
	public function testConvertLegacyToExistingEmptyHomeStorage($dataDir, $userId) {
		$this->prepareSettings($dataDir, $userId);
		$legacyStorageNumId = $this->createStorage($this->legacyStorageId);
		$newStorageNumId = $this->createStorage($this->newStorageId);

		$this->createData($this->legacyStorageId);

		$this->repair->run();

		$this->assertNull($this->getStorageId($this->legacyStorageId));
		$this->assertEquals($legacyStorageNumId, $this->getStorageId($this->newStorageId));
	}

	/**
	 * Test that legacy storages are converted to home storages
	 * when home storage already exists and the legacy storage
	 * has no data.
	 * @dataProvider settingsProvider
	 */
	public function testConvertEmptyLegacyToHomeStorage($dataDir, $userId) {
		$this->prepareSettings($dataDir, $userId);
		$legacyStorageNumId = $this->createStorage($this->legacyStorageId);
		$newStorageNumId = $this->createStorage($this->newStorageId);

		$this->createData($this->newStorageId);

		$this->repair->run();

		$this->assertNull($this->getStorageId($this->legacyStorageId));
		$this->assertEquals($newStorageNumId, $this->getStorageId($this->newStorageId));
	}

	/**
	 * Test that nothing is done when both conflicting legacy
	 * and home storage have data.
	 * @dataProvider settingsProvider
	 */
	public function testConflictNoop($dataDir, $userId) {
		$this->prepareSettings($dataDir, $userId);
		$legacyStorageNumId = $this->createStorage($this->legacyStorageId);
		$newStorageNumId = $this->createStorage($this->newStorageId);

		$this->createData($this->legacyStorageId);
		$this->createData($this->newStorageId);

		try {
			$thrown = false;
			$this->repair->run();
		}
		catch (\OC\RepairException $e) {
			$thrown = true;
		}

		$this->assertTrue($thrown);

		// storages left alone
		$this->assertEquals($legacyStorageNumId, $this->getStorageId($this->legacyStorageId));
		$this->assertEquals($newStorageNumId, $this->getStorageId($this->newStorageId));

		// did not set the done flag
		$this->assertNotEquals('yes', $this->config->getAppValue('core', 'repairlegacystoragesdone'));
	}

	/**
	 * Test that the data dir local entry is left alone
	 * @dataProvider settingsProvider
	 */
	public function testDataDirEntryNoop($dataDir, $userId) {
		$this->prepareSettings($dataDir, $userId);
		$storageId = 'local::' . $this->dataDir;
		$numId = $this->createStorage($storageId);

		$this->repair->run();

		$this->assertEquals($numId, $this->getStorageId($storageId));
	}

	/**
	 * Test that external local storages are left alone
	 * @dataProvider settingsProvider
	 */
	public function testLocalExtStorageNoop($dataDir, $userId) {
		$this->prepareSettings($dataDir, $userId);
		$storageId = 'local::/tmp/somedir/' . $this->user;
		$numId = $this->createStorage($storageId);

		$this->repair->run();

		$this->assertEquals($numId, $this->getStorageId($storageId));
	}

	/**
	 * Test that other external storages are left alone
	 * @dataProvider settingsProvider
	 */
	public function testExtStorageNoop($dataDir, $userId) {
		$this->prepareSettings($dataDir, $userId);
		$storageId = 'smb::user@password/tmp/somedir/' . $this->user;
		$numId = $this->createStorage($storageId);

		$this->repair->run();

		$this->assertEquals($numId, $this->getStorageId($storageId));
	}

	/**
	 * Provides data dir and user name
	 */
	function settingsProvider() {
		return array(
			// regular data dir
			array(
				'/tmp/oc-autotest/datadir/',
				uniqid('user_'),
			),
			// long datadir / short user
			array(
				'/tmp/oc-autotest/datadir01234567890123456789012345678901234567890123456789END/',
				uniqid('user_'),
			),
			// short datadir / long user
			array(
				'/tmp/oc-autotest/datadir/',
				'u123456789012345678901234567890123456789012345678901234567890END', // 64 chars
			),
		);
	}

	/**
	 * Only run the repair once
	 */
	public function testOnlyRunOnce() {
		$output = array();
		$this->repair->listen('\OC\Repair', 'info', function ($description) use (&$output) {
			$output[] = 'info: ' . $description;
		});

		$this->prepareSettings('/tmp/oc-autotest/datadir', uniqid('user_'));
		$this->assertNotEquals('yes', $this->config->getAppValue('core', 'repairlegacystoragesdone'));
		$this->repair->run();
		$this->assertEquals(1, count($output));
		$this->assertEquals('yes', $this->config->getAppValue('core', 'repairlegacystoragesdone'));

		$output = array();
		$this->repair->run();
		// no output which means it did not run
		$this->assertEquals(0, count($output));
		$this->assertEquals('yes', $this->config->getAppValue('core', 'repairlegacystoragesdone'));
	}
}
