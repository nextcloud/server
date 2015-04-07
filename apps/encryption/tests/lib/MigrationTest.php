<?php
 /**
 * ownCloud
 *
 * @copyright (C) 2015 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Encryption\Tests;

use OCA\Encryption\Migration;

class MigrationTest extends \Test\TestCase {

	const TEST_ENCRYPTION_MIGRATION_USER1='test_encryption_user1';
	const TEST_ENCRYPTION_MIGRATION_USER2='test_encryption_user2';
	const TEST_ENCRYPTION_MIGRATION_USER3='test_encryption_user3';

	/** @var \OC\Files\View */
	private $view;
	private $public_share_key_id = 'share_key_id';
	private $recovery_key_id = 'recovery_key_id';
	private $moduleId;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		\OC_User::createUser(self::TEST_ENCRYPTION_MIGRATION_USER1, 'foo');
		\OC_User::createUser(self::TEST_ENCRYPTION_MIGRATION_USER2, 'foo');
		\OC_User::createUser(self::TEST_ENCRYPTION_MIGRATION_USER3, 'foo');
	}

	public static function tearDownAfterClass() {
		\OC_User::deleteUser(self::TEST_ENCRYPTION_MIGRATION_USER1);
		\OC_User::deleteUser(self::TEST_ENCRYPTION_MIGRATION_USER2);
		\OC_User::deleteUser(self::TEST_ENCRYPTION_MIGRATION_USER3);
		parent::tearDownAfterClass();
	}


	public function setUp() {
		$this->view = new \OC\Files\View();
		$this->moduleId = \OCA\Encryption\Crypto\Encryption::ID;
	}

	protected function createDummyShareKeys($uid) {
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

	protected function createDummyUserKeys($uid) {
		$this->view->mkdir($uid . '/files_encryption/');
		$this->view->mkdir('/files_encryption/public_keys');
		$this->view->file_put_contents($uid . '/files_encryption/' . $uid . '.privateKey', 'privateKey');
		$this->view->file_put_contents('/files_encryption/public_keys/' . $uid . '.publicKey', 'publicKey');
	}

	protected function createDummyFileKeys($uid) {
		$this->view->mkdir($uid . '/files_encryption/keys/folder1/folder2/folder3/file3');
		$this->view->mkdir($uid . '/files_encryption/keys/folder1/folder2/file2');
		$this->view->mkdir($uid . '/files_encryption/keys/folder1/file.1');
		$this->view->mkdir($uid . '/files_encryption/keys/folder2/file.2.1');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/folder2/folder3/file3/fileKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/folder2/file2/fileKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder1/file.1/fileKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keys/folder2/file.2.1/fileKey'  , 'data');
	}

	protected function createDummyFilesInTrash($uid) {
		$this->view->mkdir($uid . '/files_trashbin/keys/file1.d5457864');
		$this->view->mkdir($uid . '/files_trashbin/keys/folder1.d7437648723/file2');
		$this->view->file_put_contents($uid . '/files_trashbin/keys/file1.d5457864/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey' , 'data');
		$this->view->file_put_contents($uid . '/files_trashbin/keys/file1.d5457864/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey' , 'data');
		$this->view->file_put_contents($uid . '/files_trashbin/keys/folder1.d7437648723/file2/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey' , 'data');

		$this->view->file_put_contents($uid . '/files_trashbin/keys/file1.d5457864/fileKey' , 'data');
		$this->view->file_put_contents($uid . '/files_trashbin/keys/folder1.d7437648723/file2/fileKey' , 'data');
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

		$this->createDummyFilesInTrash(self::TEST_ENCRYPTION_MIGRATION_USER2);

		// no user for system wide mount points
		$this->createDummyFileKeys('');
		$this->createDummyShareKeys('');

		$this->createDummySystemWideKeys();

		$m = new Migration(\OC::$server->getConfig(), new \OC\Files\View(), \OC::$server->getDatabaseConnection());
		$m->reorganizeFolderStructure();

		$this->assertTrue(
			$this->view->file_exists(
				self::TEST_ENCRYPTION_MIGRATION_USER1 . '/files_encryption/' .
				$this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.publicKey')
		);
		$this->assertTrue(
			$this->view->file_exists(
				self::TEST_ENCRYPTION_MIGRATION_USER2 . '/files_encryption/' .
				$this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.publicKey')
		);
		$this->assertTrue(
			$this->view->file_exists(
				self::TEST_ENCRYPTION_MIGRATION_USER3 . '/files_encryption/' .
				$this->moduleId . '/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.publicKey')
		);
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

	protected function verifyFilesInTrash($uid) {
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

	protected function verifyNewKeyPath($uid) {
		// private key
		if ($uid !== '') {
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

		// delete default values set by the encryption app during initialization

		/** @var \OC\DB\Connection $connection */
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->createQueryBuilder();
		$query->delete('`*PREFIX*appconfig`')
			->where($query->expr()->eq('`appid`', ':appid'))
			->setParameter('appid', 'encryption');
		$query->execute();
		$query = $connection->createQueryBuilder();
		$query->delete('`*PREFIX*preferences`')
			->where($query->expr()->eq('`appid`', ':appid'))
			->setParameter('appid', 'encryption');
		$query->execute();
	}

	public function testUpdateDB() {
		$this->prepareDB();

		$m = new Migration(\OC::$server->getConfig(), new \OC\Files\View(), \OC::$server->getDatabaseConnection());
		$m->updateDB();

		$this->verifyDB('`*PREFIX*appconfig`', 'files_encryption', 0);
		$this->verifyDB('`*PREFIX*preferences`', 'files_encryption', 0);
		$this->verifyDB('`*PREFIX*appconfig`', 'encryption', 3);
		$this->verifyDB('`*PREFIX*preferences`', 'encryption', 1);

	}

	public function verifyDB($table, $appid, $expected) {
		/** @var \OC\DB\Connection $connection */
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->createQueryBuilder();
		$query->select('`appid`')
			->from($table)
			->where($query->expr()->eq('`appid`', ':appid'))
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
		$m = new Migration(\OC::$server->getConfig(), new \OC\Files\View(), \OC::$server->getDatabaseConnection());
		\Test_Helper::invokePrivate($m, 'updateFileCache');

		// check results

		/** @var \OC\DB\Connection $connection */
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->createQueryBuilder();
		$query->select('*')
			->from('`*PREFIX*filecache`');
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
		/** @var \OC\DB\Connection $connection */
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->createQueryBuilder();
		$query->delete('`*PREFIX*filecache`');
		$query->execute();
		$query = $connection->createQueryBuilder();
		$result = $query->select('`fileid`')
			->from('`*PREFIX*filecache`')
			->setMaxResults(1)->execute()->fetchAll();
		$this->assertEmpty($result);
		$query = $connection->createQueryBuilder();
		$query->insert('`*PREFIX*filecache`')
			->values(
				array(
					'`storage`' => ':storage',
					'`path_hash`' => ':path_hash',
					'`encrypted`' => ':encrypted',
					'`size`' => ':size',
					'`unencrypted_size`' => ':unencrypted_size'
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
		$query = $connection->createQueryBuilder();
		$result = $query->select('`fileid`')
			->from('`*PREFIX*filecache`')
			->execute()->fetchAll();
		$this->assertSame(19, count($result));
	}

}
