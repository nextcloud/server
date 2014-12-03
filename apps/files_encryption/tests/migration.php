<?php
 /**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
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

namespace OCA\Files_Encryption\Tests;

class Migration extends TestCase {

	const TEST_ENCRYPTION_MIGRATION_USER1='test_encryption_user1';
	const TEST_ENCRYPTION_MIGRATION_USER2='test_encryption_user2';
	const TEST_ENCRYPTION_MIGRATION_USER3='test_encryption_user3';

	/** @var \OC\Files\View */
	private $view;
	private $public_share_key_id;
	private $recovery_key_id;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::loginHelper(self::TEST_ENCRYPTION_MIGRATION_USER1, true);
		self::loginHelper(self::TEST_ENCRYPTION_MIGRATION_USER2, true);
		self::loginHelper(self::TEST_ENCRYPTION_MIGRATION_USER3, true);
	}

	public static function tearDownAfterClass() {
		\OC_User::deleteUser(self::TEST_ENCRYPTION_MIGRATION_USER1);
		\OC_User::deleteUser(self::TEST_ENCRYPTION_MIGRATION_USER2);
		\OC_User::deleteUser(self::TEST_ENCRYPTION_MIGRATION_USER3);
		parent::tearDownAfterClass();
	}

	protected function tearDown() {
		if (\OC_DB::tableExists('encryption_test')) {
			\OC_DB::dropTable('encryption_test');
		}
		$this->assertTableNotExist('encryption_test');

		parent::tearDown();
	}

	public function setUp() {
		$this->loginHelper(self::TEST_ENCRYPTION_MIGRATION_USER1);
		$this->view = new \OC\Files\View();
		$this->public_share_key_id = \OCA\Files_Encryption\Helper::getPublicShareKeyId();
		$this->recovery_key_id = \OCA\Files_Encryption\Helper::getRecoveryKeyId();
		if (\OC_DB::tableExists('encryption_test')) {
			\OC_DB::dropTable('encryption_test');
		}
		$this->assertTableNotExist('encryption_test');
	}

	public function checkLastIndexId() {
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*share` ('
			.' `item_type`, `item_source`, `item_target`, `share_type`,'
			.' `share_with`, `uid_owner`, `permissions`, `stime`, `file_source`,'
			.' `file_target`, `token`, `parent`, `expiration`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
		$query->bindValue(1, 'file');
		$query->bindValue(2, 949);
		$query->bindValue(3, '/949');
		$query->bindValue(4, 0);
		$query->bindValue(5, 'migrate-test-user');
		$query->bindValue(6, 'migrate-test-owner');
		$query->bindValue(7, 23);
		$query->bindValue(8, 1402493312);
		$query->bindValue(9, 0);
		$query->bindValue(10, '/migration.txt');
		$query->bindValue(11, null);
		$query->bindValue(12, null);
		$query->bindValue(13, null);
		$this->assertEquals(1, $query->execute());

		$this->assertNotEquals('0', \OC_DB::insertid('*PREFIX*share'));

		// cleanup
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*share` WHERE `file_target` = ?');
		$query->bindValue(1, '/migration.txt');
		$this->assertEquals(1, $query->execute());

	}

	public function testBrokenLastIndexId() {

		// create test table
		$this->checkLastIndexId();
		\OC_DB::createDbFromStructure(__DIR__ . '/encryption_table.xml');
		$this->checkLastIndexId();
	}

	/**
	 * @param string $table
	 */
	public function assertTableNotExist($table) {
		$type = \OC_Config::getValue( "dbtype", "sqlite" );
		if( $type == 'sqlite' || $type == 'sqlite3' ) {
			// sqlite removes the tables after closing the DB
			$this->assertTrue(true);
		} else {
			$this->assertFalse(\OC_DB::tableExists($table), 'Table ' . $table . ' exists.');
		}
	}

	protected function createDummyShareKeys($uid) {
		$this->view->mkdir($uid . '/files_encryption/share-keys/folder1/folder2/folder3');
		$this->view->mkdir($uid . '/files_encryption/share-keys/folder2/');
		$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder1/folder2/folder3/file3.' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder1/folder2/folder3/file3.' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder1/folder2/folder3/file3.' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder1/folder2/file2.' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder1/folder2/file2.' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder1/folder2/file2.' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder1/file.1.' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder1/file.1.' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder1/file.1.' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder2/file.2.1.' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder2/file.2.1.' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder2/file.2.1.' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'  , 'data');
		if ($this->public_share_key_id) {
			$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder2/file.2.1.' . $this->public_share_key_id . '.shareKey'  , 'data');
		}
		if ($this->recovery_key_id) {
			$this->view->file_put_contents($uid . '/files_encryption/share-keys/folder2/file.2.1.' . $this->recovery_key_id . '.shareKey'  , 'data');
		}
	}

	protected function createDummyFileKeys($uid) {
		$this->view->mkdir($uid . '/files_encryption/keyfiles/folder1/folder2/folder3');
		$this->view->mkdir($uid . '/files_encryption/keyfiles/folder2/');
		$this->view->file_put_contents($uid . '/files_encryption/keyfiles/folder1/folder2/folder3/file3.key'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keyfiles/folder1/folder2/file2.key'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keyfiles/folder1/file.1.key'  , 'data');
		$this->view->file_put_contents($uid . '/files_encryption/keyfiles/folder2/file.2.1.key'  , 'data');
	}

	protected function createDummyFilesInTrash($uid) {
		$this->view->mkdir($uid . '/files_trashbin/share-keys');
		$this->view->mkdir($uid . '/files_trashbin/share-keys/folder1.d7437648723');
		$this->view->file_put_contents($uid . '/files_trashbin/share-keys/file1.' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey.d5457864' , 'data');
		$this->view->file_put_contents($uid . '/files_trashbin/share-keys/file1.' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey.d5457864' , 'data');
		$this->view->file_put_contents($uid . '/files_trashbin/share-keys/folder1.d7437648723/file2.' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey' , 'data');

		$this->view->mkdir($uid . '/files_trashbin/keyfiles');
		$this->view->mkdir($uid . '/files_trashbin/keyfiles/folder1.d7437648723');
		$this->view->file_put_contents($uid . '/files_trashbin/keyfiles/file1.key.d5457864' , 'data');
		$this->view->file_put_contents($uid . '/files_trashbin/keyfiles/folder1.d7437648723/file2.key' , 'data');
	}

	protected function createDummySystemWideKeys() {
		$this->view->mkdir('owncloud_private_key');
		$this->view->file_put_contents('owncloud_private_key/systemwide_1.private.key', 'data');
		$this->view->file_put_contents('owncloud_private_key/systemwide_2.private.key', 'data');
	}

	public function testMigrateToNewFolderStructure() {

		// go back to the state before migration
		$this->view->rename('/files_encryption/public_keys', '/public-keys');
		$this->view->rename('/public-keys/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.publicKey', '/public-keys/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.public.key');
		$this->view->rename('/public-keys/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.publicKey', '/public-keys/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.public.key');
		$this->view->rename('/public-keys/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.publicKey', '/public-keys/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.public.key');
		$this->view->deleteAll(self::TEST_ENCRYPTION_MIGRATION_USER1 . '/files_encryption/keys');
		$this->view->deleteAll(self::TEST_ENCRYPTION_MIGRATION_USER2 . '/files_encryption/keys');
		$this->view->deleteAll(self::TEST_ENCRYPTION_MIGRATION_USER3 . '/files_encryption/keys');
		$this->view->rename(self::TEST_ENCRYPTION_MIGRATION_USER1 . '/files_encryption/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.privateKey',
				self::TEST_ENCRYPTION_MIGRATION_USER1 . '/files_encryption/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.private.key');
		$this->view->rename(self::TEST_ENCRYPTION_MIGRATION_USER2 . '/files_encryption/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.privateKey',
				self::TEST_ENCRYPTION_MIGRATION_USER2 . '/files_encryption/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.private.key');
		$this->view->rename(self::TEST_ENCRYPTION_MIGRATION_USER3 . '/files_encryption/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.privateKey',
				self::TEST_ENCRYPTION_MIGRATION_USER3 . '/files_encryption/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.private.key');

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

		$m = new \OCA\Files_Encryption\Migration();
		$m->reorganizeFolderStructure();

		// TODO Verify that all files at the right place
		$this->assertTrue($this->view->file_exists('/files_encryption/public_keys/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.publicKey'));
		$this->assertTrue($this->view->file_exists('/files_encryption/public_keys/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.publicKey'));
		$this->assertTrue($this->view->file_exists('/files_encryption/public_keys/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.publicKey'));
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
		$this->view->file_exists($uid . '/files_trashbin/keys/file1.d5457864/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey.d5457864' , 'data');
		$this->view->file_exists($uid . '/files_trashbin/keys/file1.d5457864/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey.d5457864' , 'data');
		$this->view->file_exists($uid . '/files_trashbin/keys/folder1.d7437648723/file2/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey' , 'data');

		// file keys
		$this->view->file_exists($uid . '/files_trashbin/keys/file1.d5457864/fileKey.d5457864' , 'data');
		$this->view->file_exists($uid . '/files_trashbin/keyfiles/file1.d5457864/fileKey.d5457864' , 'data');
		$this->view->file_exists($uid . '/files_trashbin/keyfiles/folder1.d7437648723/file2/fileKey' , 'data');
	}

	protected function verifyNewKeyPath($uid) {
		// private key
		if ($uid !== '') {
			$this->assertTrue($this->view->file_exists($uid . '/files_encryption/' . $uid . '.privateKey'));
		}
		// file keys
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder1/folder2/folder3/file3/fileKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder1/folder2/file2/fileKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder1/file.1/fileKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder2/file.2.1/fileKey'));
		// share keys
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder1/folder2/folder3/file3/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder1/folder2/folder3/file3/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder1/folder2/folder3/file3/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder1/folder2/file2/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder1/folder2/file2/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder1/folder2/file2/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder1/file.1/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder1/file.1/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder1/file.1/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder2/file.2.1/' . self::TEST_ENCRYPTION_MIGRATION_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder2/file.2.1/' . self::TEST_ENCRYPTION_MIGRATION_USER2 . '.shareKey'));
		$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder2/file.2.1/' . self::TEST_ENCRYPTION_MIGRATION_USER3 . '.shareKey'));
		if ($this->public_share_key_id) {
			$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder2/file.2.1/' . $this->public_share_key_id . '.shareKey'));
		}
		if ($this->recovery_key_id) {
			$this->assertTrue($this->view->file_exists($uid . '/files_encryption/keys/folder2/file.2.1/' . $this->recovery_key_id . '.shareKey'));
		}
	}
}
