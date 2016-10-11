<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
namespace Test\Repair;

/**
 * Test for fixing the userRoot and avatar permissions
 *
 * @group DB
 *
 * @see \OC\Repair\AvatarPermissionsTest
 */
class AvatarPermissionsTest extends \Test\TestCase {

	/** @var \OC\Repair\AvatarPermissions */
	protected $repair;

	/** @var \OCP\IDBConnection */
	protected $connection;

	protected function setUp() {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->repair = new \OC\Repair\AvatarPermissions($this->connection);
		$this->cleanUpTables();
	}

	protected function tearDown() {
		$this->cleanUpTables();

		parent::tearDown();
	}

	protected function cleanUpTables() {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('filecache')->execute();
		$qb->delete('storages')->execute();
	}

	public function dataFixUserRootPermissions() {
		return [
			['home::user', '', 0, 23],
			['home::user', 'foo', 0, 0],
			['home::user', 'avatar.jpg', 0, 0],
			['ABC::user', '', 0, 0],
			['ABC::user', 'foo', 0, 0],
		];
	}

	/**
	 * @dataProvider dataFixUserRootPermissions
	 *
	 * @param string $storageId
	 * @param string $path
	 * @param int $permissionsBefore
	 * @param int $permissionsAfter
	 */
	public function testFixUserRootPermissions($storageId, $path, $permissionsBefore, $permissionsAfter) {
		$userStorage = $this->addStorage($storageId);
		$userHome = $this->addFileCacheEntry($userStorage, $path, $permissionsBefore);

		$this->invokePrivate($this->repair, 'fixUserRootPermissions', []);

		$this->verifyPermissions($userHome, $permissionsAfter);
	}

	public function dataFixAvatarPermissions() {
		return [
			['home::user', '', 0, 0],
			['home::user', 'avatar.jpg', 0, 27],
			['home::user', 'avatar.png', 0, 27],
			['home::user', 'avatar.32.png', 0, 27],
			['home::user', 'mine.txt', 0, 0],
			['ABC::user', '', 0, 0],
			['ABC::user', 'avatar.jpg', 0, 0],
			['ABC::user', 'avatar.png', 0, 0],
			['ABC::user', 'avatar.32.png', 0, 0],
			['ABC::user', 'mine.txt', 0, 0],
		];
	}

	/**
	 * @dataProvider dataFixAvatarPermissions
	 *
	 * @param string $storageId
	 * @param string $path
	 * @param int $permissionsBefore
	 * @param int $permissionsAfter
	 */
	public function testFixAvatarPermissions($storageId, $path, $permissionsBefore, $permissionsAfter) {
		$userStorage = $this->addStorage($storageId);
		$userHome = $this->addFileCacheEntry($userStorage, $path, $permissionsBefore);

		$this->invokePrivate($this->repair, 'fixAvatarPermissions', []);

		$this->verifyPermissions($userHome, $permissionsAfter);
	}

	/**
	 * Add a new storage
	 *
	 * @param string $id
	 * @return int The numeric id
	 */
	protected function addStorage($id) {
		$qb = $this->connection->getQueryBuilder();

		$qb->insert('storages')
			->values([
				'id' => $qb->createNamedParameter($id)
			]);

		$qb->execute();

		return $qb->getLastInsertId();
	}

	/**
	 * Add a filecache entry
	 *
	 * @param int $storage
	 * @param string $path
	 * @param int $permissions
	 *
	 * @return int The fileid
	 */
	protected function addFileCacheEntry($storage, $path, $permissions) {
		$qb = $this->connection->getQueryBuilder();

		$qb->insert('filecache')
			->values([
				'path' => $qb->createNamedParameter($path),
				'path_hash' => $qb->createNamedParameter(md5($path)),
				'parent' => $qb->createNamedParameter(42),
				'mimetype' => $qb->createNamedParameter(23),
				'mimepart' => $qb->createNamedParameter(32),
				'size' => $qb->createNamedParameter(16),
				'mtime' => $qb->createNamedParameter(1),
				'storage_mtime' => $qb->createNamedParameter(2),
				'encrypted' => $qb->createNamedParameter(0),
				'unencrypted_size' => $qb->createNamedParameter(0),
				'storage' => $qb->createNamedParameter($storage),
				'permissions' => $qb->createNamedParameter($permissions),
			]);

		$qb->execute();

		return $qb->getLastInsertId();
	}

	/**
	 * @param int $fileId
	 * @param int $permissions
	 */
	protected function verifyPermissions($fileId, $permissions) {
		$qb = $this->connection->getQueryBuilder();

		$qb->select('permissions')
			->from('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId)));

		$cursor = $qb->execute();

		$data = $cursor->fetch();
		$cursor->closeCursor();

		$this->assertSame($permissions, (int)$data['permissions']);
	}


}
