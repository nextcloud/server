<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Encryption\Tests;

use OCA\Encryption\Migration;
use OCP\ILogger;

class MigrationTest extends \Test\TestCase {

	const TEST_ENCRYPTION_MIGRATION_USER1='test_encryption_user1';
	const TEST_ENCRYPTION_MIGRATION_USER2='test_encryption_user2';
	const TEST_ENCRYPTION_MIGRATION_USER3='test_encryption_user3';

	/** @var \OC\Files\View */
	private $view;
	private $public_share_key_id = 'share_key_id';
	private $recovery_key_id = 'recovery_key_id';
	private $moduleId;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | ILogger */
	private $logger;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		\OC::$server->getUserManager()->createUser(self::TEST_ENCRYPTION_MIGRATION_USER1, 'foo');
		\OC::$server->getUserManager()->createUser(self::TEST_ENCRYPTION_MIGRATION_USER2, 'foo');
		\OC::$server->getUserManager()->createUser(self::TEST_ENCRYPTION_MIGRATION_USER3, 'foo');
	}

	public static function tearDownAfterClass() {
		$user = \OC::$server->getUserManager()->get(self::TEST_ENCRYPTION_MIGRATION_USER1);
		if ($user !== null) { $user->delete(); }
		$user = \OC::$server->getUserManager()->get(self::TEST_ENCRYPTION_MIGRATION_USER2);
		if ($user !== null) { $user->delete(); }
		$user = \OC::$server->getUserManager()->get(self::TEST_ENCRYPTION_MIGRATION_USER3);
		if ($user !== null) { $user->delete(); }
		parent::tearDownAfterClass();
	}


	public function setUp() {
		$this->logger = $this->getMockBuilder('\OCP\ILogger')->disableOriginalConstructor()->getMock();
		$this->view = new \OC\Files\View();
		$this->moduleId = \OCA\Encryption\Crypto\Encryption::ID;
	}

	/**
	 * @param string $uid
	 */
	protected function createDummyShareKeys($uid) {
		$this->loginAsUser($uid);

		$this->view->mkdir($uid . '/files_encryption/keys/folder1/folder2/folder3/file3');
		$this->view->mkdir($uid . '/files_encryption/keys/folder1/folder2/file2');
		$this->view->mkdir($uid . '/files_encryption/keys/folder1/file.1');
		$this->view->mkdir($uid . '/files_encryption/keys/folder2/file.2.1');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/folder2/folder3/file3/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/folder2/folder3/file3/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/folder2/folder3/file3/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/folder2/file2/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/folder2/file2/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/folder2/file2/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/file.1/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/file.1/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/file.1/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder2/file.2.1/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder2/file.2.1/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder2/file.2.1/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'  , 'data');
		if ($this->public_share_key_id) {
			$this->view->file_put_contents($uid . '/files_encryption/keys/folder2/file.2.1/' . $this->public_share_key_id . '.shareKey'  , 'data');
		}
		if ($this->recovery_key_id) {
			$this->view->file_put_contents($uid . '/files_encryption/keys/folder2/file.2.1/' . $this->recovery_key_id . '.shareKey'  , 'data');
		}
	}

	/**
	 * @param string $uid
	 */
	protected function createDummyUserKeys($uid) {
		$this->loginAsUser($uid);

		$this->view->mkdir($uid . '/files_encryption/');
		$this->view->mkdir('/files_encryption/public_keys');
		$this->view->file_put_contents($uid . '/files_encryption/' . $uid . '.privateKey', 'privateKey');
		$this->view->file_put_contents('/files_encryption/public_keys/' . $uid . '.publicKey', 'publicKey');
	}

	/**
	 * @param string $uid
	 */
	protected function createDummyFileKeys($uid) {
		$this->loginAsUser($uid);

		$this->view->mkdir($uid . '/files_encryption/keys/folder1/folder2/folder3/file3');
		$this->view->mkdir($uid . '/files_encryption/keys/folder1/folder2/file2');
		$this->view->mkdir($uid . '/files_encryption/keys/folder1/file.1');
		$this->view->mkdir($uid . '/files_encryption/keys/folder2/file.2.1');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/folder2/folder3/file3/fileKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/folder2/file2/fileKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/file.1/fileKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder2/file.2.1/fileKey'  , 'data');
	}

	/**
	 * @param string $uid
	 */
	protected function createDummyFiles($uid) {
		$this->loginAsUser($uid);

		$this->view->mkdir($uid . '/files/folder1/folder2/folder3/file3');
		$this->view->mkdir($uid . '/files/folder1/folder2/file2');
		$this->view->mkdir($uid . '/files/folder1/file.1');
		$this->view->mkdir($uid . '/files/folder2/file.2.1');
		$this->view->file_put_contents($uid . '/files/folder1/folder2/folder3/file3/fileKey'  , 'data');
		$this->view->file_put_contents($uid . '/files/folder1/folder2/file2/fileKey'  , 'data');
		$this->view->file_put_contents($uid . '/files/folder1/file.1/fileKey'  , 'data');
		$this->view->file_put_contents($uid . '/files/folder2/file.2.1/fileKey'  , 'data');
	}

	/**
	 * @param string $uid
	 */
	protected function createDummyFilesInTrash($uid) {
		$this->loginAsUser($uid);

		$this->view->mkdir($uid . '/files_trashbin/keys/file1.d5457864');
		$this->view->mkdir($uid . '/files_trashbin/keys/folder1.d7437648723/file2');
		$this->view->file_put_contents($uid . '/files_trashbin/keys/file1.d5457864/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey' , 'data');
		$this->view->file_put_contents($uid . '/files_trashbin/keys/file1.d5457864/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey' , 'data');
		$this->view->file_put_contents($uid . '/files_trashbin/keys/folder1.d7437648723/file2/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey' , 'data');

		$this->view->file_put_contents($uid . '/files_trashbin/keys/file1.d5457864/fileKey' , 'data');
		$this->view->file_put_contents($uid . '/files_trashbin/keys/folder1.d7437648723/file2/fileKey' , 'data');

		// create the files itself
		$this->view->mkdir($uid . '/files_trashbin/folder1.d7437648723');
		$this->view->file_put_contents($uid . '/files_trashbin/file1.d5457864' , 'data');
		$this->view->file_put_contents($uid . '/files_trashbin/folder1.d7437648723/file2' , 'data');
	}

	protected function createDummySystemWideKeys() {
		$this->view->mkdir('files_encryption');
		$this->view->mkdir('files_encryption/public_keys');
		$this->view->file_put_contents('files_encryption/systemwide_1.privateKey', 'data');
		$this->view->file_put_contents('files_encryption/systemwide_2.privateKey', 'data');
		$this->view->file_put_contents('files_encryption/public_keys/systemwide_1.publicKey', 'data');
		$this->view->file_put_contents('files_encryption/public_keys/systemwide_2.publicKey', 'data');
	}

	public function testMigrateToNewFolderStructure() {
		$this->createDummyUserKeys(self::TEST_ENCRYPTION_MIGRATION_USER1);
		$this->createDummyUserKeys(self::TEST_ENCRYPTION_MIGRATION_USER2);
		$this->createDummyUserKeys(self::TEST_ENCRYPTION_MIGRATION_USER3);

		$this->createDummyShareKeys(self::TEST_ENCRYPTION_MIGRATION_USER1);
		$this->createDummyShareKeys(self::TEST_ENCRYPTION_MIGRATION_USER2);
		$this->createDummyShareKeys(self::TEST_ENCRYPTION_MIGRATION_USER3);

		$this->createDummyFileKeys(self::TEST_ENCRYPTION_MIGRATION_USER1);
		$this->createDummyFileKeys(self::TEST_ENCRYPTION_MIGRATION_USER2);
		$this->createDummyFileKeys(self::TEST_ENCRYPTION_MIGRATION_USER3);

		$this->createDummyFiles(self::TEST_ENCRYPTION_MIGRATION_USER1);
		$this->createDummyFiles(self::TEST_ENCRYPTION_MIGRATION_USER2);
		$this->createDummyFiles(self::TEST_ENCRYPTION_MIGRATION_USER3);

		$this->createDummyFilesInTrash(self::TEST_ENCRYPTION_MIGRATION_USER2);

		// no user for system wide mount points
		$this->createDummyFileKeys('');
		$this->createDummyShareKeys('');

		$this->createDummySystemWideKeys();

		/** @var \PHPUnit_Framework_MockObject_MockObject|\OCA\Encryption\Migration $m */
		$m = $this->getMockBuilder('OCA\Encryption\Migration')
			->setConstructorArgs(
				[
					\OC::$server->getConfig(),
					new \OC\Files\View(),
					\OC::$server->getDatabaseConnection(),
					$this->logger
				]
			)->setMethods(['getSystemMountPoints'])->getMock();

		$m->expects($this->any())->method('getSystemMountPoints')
			->will($this->returnValue([['mountpoint' => 'folder1'], ['mountpoint' => 'folder2']]));

		$m->reorganizeFolderStructure();
		// even if it runs twice folder should always move only once
		$m->reorganizeFolderStructure();

		$this->loginAsUser(self::TEST_ENCRYPTION_MIGRATION_USER1);

		$this->assertTrue(
			$this->view->file_exists(
				self::TEST_ENCRYPTION_MIGRATION_USER1 . '/files_encryption/' .
				$this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.publicKey')
		);

		$this->loginAsUser(self::TEST_ENCRYPTION_MIGRATION_USER2);

		$this->assertTrue(
			$this->view->file_exists(
				self::TEST_ENCRYPTION_MIGRATION_USER2 . '/files_encryption/' .
				$this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.publicKey')
		);

		$this->loginAsUser(self::TEST_ENCRYPTION_MIGRATION_USER3);

		$this->assertTrue(
			$this->view->file_exists(
				self::TEST_ENCRYPTION_MIGRATION_USER3 . '/files_encryption/' .
				$this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.publicKey')
		);

		$this->loginAsUser(self::TEST_ENCRYPTION_MIGRATION_USER1);

		$this->assertTrue(
			$this->view->file_exists(
			    '/files_encryption/' . $this->moduleId . '/systemwide_1.publicKey')
		);
		$this->assertTrue(
			$this->view->file_exists(
				'/files_encryption/' . $this->moduleId . '/systemwide_2.publicKey')
		);

		$this->verifyNewKeyPath(self::TEST_ENCRYPTION_MIGRATION_USER1);
		$this->verifyNewKeyPath(self::TEST_ENCRYPTION_MIGRATION_USER2);
		$this->verifyNewKeyPath(self::TEST_ENCRYPTION_MIGRATION_USER3);
		// system wide keys
		$this->verifyNewKeyPath('');
		// trash
		$this->verifyFilesInTrash(self::TEST_ENCRYPTION_MIGRATION_USER2);

	}

	/**
	 * @param string $uid
	 */
	protected function verifyFilesInTrash($uid) {
		$this->loginAsUser($uid);

		// share keys
		$this->assertTrue(
			$this->view->file_exists($uid . '/files_encryption/keys/files_trashbin/file1.d5457864/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey')
			);
		$this->assertTrue(
			$this->view->file_exists($uid . '/files_encryption/keys/files_trashbin/file1.d5457864/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey')
			);
		$this->assertTrue(
			$this->view->file_exists($uid . '/files_encryption/keys/files_trashbin/folder1.d7437648723/file2/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey')
			);

		// file keys
		$this->assertTrue(
			$this->view->file_exists($uid . '/files_encryption/keys/files_trashbin/file1.d5457864/' . $this->moduleId . '/fileKey')
		);

		$this->assertTrue(
		$this->view->file_exists($uid . '/files_encryption/keys/files_trashbin/file1.d5457864/' . $this->moduleId . '/fileKey')
		);
		$this->assertTrue(
			$this->view->file_exists($uid . '/files_encryption/keys/files_trashbin/folder1.d7437648723/file2/' . $this->moduleId . '/fileKey')
		);
	}

	/**
	 * @param string $uid
	 */
	protected function verifyNewKeyPath($uid) {
		// private key
		if ($uid !== '') {
			$this->loginAsUser($uid);
			$this->assertTrue($this->view->file_exists($uid . '/files_encryption/' . $this->moduleId . '/'. $uid . '.privateKey'));
		}
		// file keys
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder1/folder2/folder3/file3/' . $this->moduleId . '/fileKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder1/folder2/file2/' . $this->moduleId . '/fileKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder1/file.1/' . $this->moduleId . '/fileKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder2/file.2.1/' .$this->moduleId . '/fileKey'));
		// share keys
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder1/folder2/folder3/file3/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder1/folder2/folder3/file3/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder1/folder2/folder3/file3/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder1/folder2/file2/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder1/folder2/file2/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder1/folder2/file2/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder1/file.1/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder1/file.1/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder1/file.1/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder2/file.2.1/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder2/file.2.1/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder2/file.2.1/' . $this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'));
		if ($this->public_share_key_id) {
			$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder2/file.2.1/' . $this->moduleId . '/' . $this->public_share_key_id . '.shareKey'));
		}
		if ($this->recovery_key_id) {
			$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/files/folder2/file.2.1/' . $this->moduleId . '/' . $this->recovery_key_id . '.shareKey'));
		}
	}

	private function prepareDB() {
		$config = \OC::$server->getConfig();
		$config->setAppValue('files_encryption', 'recoveryKeyId', 'recovery_id');
		$config->setAppValue('files_encryption', 'publicShareKeyId', 'share_id');
		$config->setAppValue('files_encryption', 'recoveryAdminEnabled', '1');
		$config->setUserValue(self::TEST_ENCRYPTION_MIGRATION_USER1, 'files_encryption', 'recoverKeyEnabled', '1');

		//$this->invokePrivate($config, 'cache', [[]]);
		$cache = $this->invokePrivate(\OC::$server->getAppConfig(), 'cache');
		unset($cache['encryption']);
		unset($cache['files_encryption']);
		$this->invokePrivate(\OC::$server->getAppConfig(), 'cache', [$cache]);

		// delete default values set by the encryption app during initialization

		/** @var \OCP\IDBConnection $connection */
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->getQueryBuilder();
		$query->delete('appconfig')
			->where($query->expr()->eq('appid', $query->createParameter('appid')))
			->setParameter('appid', 'encryption');
		$query->execute();
		$query = $connection->getQueryBuilder();
		$query->delete('preferences')
			->where($query->expr()->eq('appid', $query->createParameter('appid')))
			->setParameter('appid', 'encryption');
		$query->execute();
	}

	public function testUpdateDB() {
		$this->prepareDB();

		$m = new Migration(\OC::$server->getConfig(), new \OC\Files\View(), \OC::$server->getDatabaseConnection(), $this->logger);
		$this->invokePrivate($m, 'installedVersion', ['0.7']);
		$m->updateDB();

		$this->verifyDB('appconfig', 'files_encryption', 0);
		$this->verifyDB('preferences', 'files_encryption', 0);
		$this->verifyDB('appconfig', 'encryption', 3);
		$this->verifyDB('preferences', 'encryption', 1);

	}

	/**
	 * test update db if the db already contain some existing new values
	 */
	public function testUpdateDBExistingNewConfig() {
		$this->prepareDB();
		$config = \OC::$server->getConfig();
		$config->setAppValue('encryption', 'publicShareKeyId', 'wrong_share_id');
		$config->setUserValue(self::TEST_ENCRYPTION_MIGRATION_USER1, 'encryption', 'recoverKeyEnabled', '9');

		$m = new Migration(\OC::$server->getConfig(), new \OC\Files\View(), \OC::$server->getDatabaseConnection(), $this->logger);
		$this->invokePrivate($m, 'installedVersion', ['0.7']);
		$m->updateDB();

		$this->verifyDB('appconfig', 'files_encryption', 0);
		$this->verifyDB('preferences', 'files_encryption', 0);
		$this->verifyDB('appconfig', 'encryption', 3);
		$this->verifyDB('preferences', 'encryption', 1);

		// check if the existing values where overwritten correctly
		/** @var \OC\DB\Connection $connection */
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->getQueryBuilder();
		$query->select('configvalue')
			->from('appconfig')
			->where($query->expr()->andX(
				$query->expr()->eq('appid', $query->createParameter('appid')),
				$query->expr()->eq('configkey', $query->createParameter('configkey'))
			))
			->setParameter('appid', 'encryption')
			->setParameter('configkey', 'publicShareKeyId');
		$result = $query->execute();
		$value = $result->fetch();
		$this->assertTrue(isset($value['configvalue']));
		$this->assertSame('share_id', $value['configvalue']);

		$query = $connection->getQueryBuilder();
		$query->select('configvalue')
			->from('preferences')
			->where($query->expr()->andX(
				$query->expr()->eq('appid', $query->createParameter('appid')),
				$query->expr()->eq('configkey', $query->createParameter('configkey')),
				$query->expr()->eq('userid', $query->createParameter('userid'))
			))
			->setParameter('appid', 'encryption')
			->setParameter('configkey', 'recoverKeyEnabled')
			->setParameter('userid', self::TEST_ENCRYPTION_MIGRATION_USER1);
		$result = $query->execute();
		$value = $result->fetch();
		$this->assertTrue(isset($value['configvalue']));
		$this->assertSame('1', $value['configvalue']);

	}

	/**
	 * @param string $table
	 * @param string $appid
	 * @param integer $expected
	 */
	public function verifyDB($table, $appid, $expected) {
		/** @var \OCP\IDBConnection $connection */
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->getQueryBuilder();
		$query->select('appid')
			->from($table)
			->where($query->expr()->eq('appid', $query->createParameter('appid')))
			->setParameter('appid', $appid);
		$result = $query->execute();
		$values = $result->fetchAll();
		$this->assertSame($expected,
			count($values)
		);
	}

	/**
	 * test update of the file cache
	 */
	public function testUpdateFileCache() {
		$this->prepareFileCache();
		$m = new Migration(\OC::$server->getConfig(), new \OC\Files\View(), \OC::$server->getDatabaseConnection(), $this->logger);
		$this->invokePrivate($m, 'installedVersion', ['0.7']);
		self::invokePrivate($m, 'updateFileCache');

		// check results

		/** @var \OCP\IDBConnection $connection */
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->getQueryBuilder();
		$query->select('*')
			->from('filecache');
		$result = $query->execute();
		$entries = $result->fetchAll();
		foreach($entries as $entry) {
			if ((int)$entry['encrypted'] === 1) {
				$this->assertSame((int)$entry['unencrypted_size'], (int)$entry['size']);
			} else {
				$this->assertSame((int)$entry['unencrypted_size'] - 2, (int)$entry['size']);
			}
		}


	}

	public function prepareFileCache() {
		/** @var \OCP\IDBConnection $connection */
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->getQueryBuilder();
		$query->delete('filecache');
		$query->execute();
		$query = $connection->getQueryBuilder();
		$result = $query->select('fileid')
			->from('filecache')
			->setMaxResults(1)->execute()->fetchAll();
		$this->assertEmpty($result);
		$query = $connection->getQueryBuilder();
		$query->insert('filecache')
			->values(
				array(
					'storage' => $query->createParameter('storage'),
					'path_hash' => $query->createParameter('path_hash'),
					'encrypted' => $query->createParameter('encrypted'),
					'size' => $query->createParameter('size'),
					'unencrypted_size' => $query->createParameter('unencrypted_size'),
				)
			);
		for ($i = 1; $i < 20; $i++) {
			$query->setParameter('storage', 1)
				->setParameter('path_hash', $i)
				->setParameter('encrypted', $i % 2)
				->setParameter('size', $i)
				->setParameter('unencrypted_size', $i + 2);
			$this->assertSame(1,
				$query->execute()
			);
		}
		$query = $connection->getQueryBuilder();
		$result = $query->select('fileid')
			->from('filecache')
			->execute()->fetchAll();
		$this->assertSame(19, count($result));
	}

	/**
	 * @dataProvider dataTestGetTargetDir
	 */
	public function testGetTargetDir($user, $keyPath, $filename, $trash, $systemMounts, $expected) {

		$view = $this->getMockBuilder('\OC\Files\View')
			->disableOriginalConstructor()->getMock();
		$view->expects($this->any())->method('file_exists')->willReturn(true);

		$m = $this->getMockBuilder('OCA\Encryption\Migration')
			->setConstructorArgs(
				[
					\OC::$server->getConfig(),
					$view,
					\OC::$server->getDatabaseConnection(),
					$this->logger
				]
			)->setMethods(['getSystemMountPoints'])->getMock();

		$m->expects($this->any())->method('getSystemMountPoints')
			->willReturn($systemMounts);

		$this->assertSame($expected,
			$this->invokePrivate($m, 'getTargetDir', [$user, $keyPath, $filename, $trash])
		);
	}

	public function dataTestGetTargetDir() {
		return [
			[
				'user1',
				'/files_encryption/keys/foo/bar.txt',
				'user1.shareKey',
				false,
				[],
				'user1/files_encryption/keys/files/foo/bar.txt/OC_DEFAULT_MODULE/user1.shareKey'
			],
			[
				'user1',
				'/files_trashbin/keys/foo/bar.txt',
				'user1.shareKey',
				true,
				[],
				'user1/files_encryption/keys/files_trashbin/foo/bar.txt/OC_DEFAULT_MODULE/user1.shareKey'
			],
			[
				'',
				'/files_encryption/keys/foo/bar.txt',
				'user1.shareKey',
				false,
				[['mountpoint' => 'foo']],
				'/files_encryption/keys/files/foo/bar.txt/OC_DEFAULT_MODULE/user1.shareKey'
			],
			[
				'',
				'/files_encryption/keys/foo/bar.txt',
				'user1.shareKey',
				false,
				[['mountpoint' => 'foobar']],
				false
			],
			[
				'',
				'/files_encryption/keys/foobar/bar.txt',
				'user1.shareKey',
				false,
				[['mountpoint' => 'foo']],
				false
			]
		];
	}

}
