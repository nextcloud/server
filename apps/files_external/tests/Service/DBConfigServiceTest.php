<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Service;

use OCA\Files_External\Service\DBConfigService;
use OCP\IDBConnection;
use OCP\Security\ICrypto;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class DBConfigServiceTest extends TestCase {
	private IDBConnection $connection;
	private DBConfigService $dbConfig;

	private array $mounts = [];

	protected function setUp(): void {
		parent::setUp();
		$this->connection = Server::get(IDBConnection::class);
		$this->dbConfig = new DBConfigService($this->connection, Server::get(ICrypto::class));
	}

	protected function tearDown(): void {
		foreach ($this->mounts as $mount) {
			$this->dbConfig->removeMount($mount);
		}
		$this->mounts = [];
		parent::tearDown();
	}

	private function addMount(string $mountPoint, string $storageBackend, string $authBackend, int $priority, int $type) {
		$id = $this->dbConfig->addMount($mountPoint, $storageBackend, $authBackend, $priority, $type);
		$this->mounts[] = $id;
		return $id;
	}

	public function testAddSimpleMount(): void {
		$id = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEquals('/test', $mount['mount_point']);
		$this->assertEquals('foo', $mount['storage_backend']);
		$this->assertEquals('bar', $mount['auth_backend']);
		$this->assertEquals(100, $mount['priority']);
		$this->assertEquals(DBConfigService::MOUNT_TYPE_ADMIN, $mount['type']);
		$this->assertEquals([], $mount['applicable']);
		$this->assertEquals([], $mount['config']);
		$this->assertEquals([], $mount['options']);
	}

	public function testAddApplicable(): void {
		$id = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$this->dbConfig->addApplicable($id, DBConfigService::APPLICABLE_TYPE_USER, 'test');

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEquals([
			['type' => DBConfigService::APPLICABLE_TYPE_USER, 'value' => 'test', 'mount_id' => $id]
		], $mount['applicable']);

		$this->dbConfig->addApplicable($id, DBConfigService::APPLICABLE_TYPE_GROUP, 'bar');
		$this->dbConfig->addApplicable($id, DBConfigService::APPLICABLE_TYPE_GLOBAL, null);

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEqualsCanonicalizing([
			['type' => DBConfigService::APPLICABLE_TYPE_USER, 'value' => 'test', 'mount_id' => $id],
			['type' => DBConfigService::APPLICABLE_TYPE_GROUP, 'value' => 'bar', 'mount_id' => $id],
			['type' => DBConfigService::APPLICABLE_TYPE_GLOBAL, 'value' => null, 'mount_id' => $id]
		], $mount['applicable']);
	}

	public function testAddApplicableDouble(): void {
		$id = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$this->dbConfig->addApplicable($id, DBConfigService::APPLICABLE_TYPE_USER, 'test');
		$this->dbConfig->addApplicable($id, DBConfigService::APPLICABLE_TYPE_USER, 'test');

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEquals([
			['type' => DBConfigService::APPLICABLE_TYPE_USER, 'value' => 'test', 'mount_id' => $id]
		], $mount['applicable']);
	}

	public function testDeleteMount(): void {
		$id = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);

		$this->dbConfig->removeMount($id);

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEquals(null, $mount);
	}

	public function testRemoveApplicable(): void {
		$id = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$this->dbConfig->addApplicable($id, DBConfigService::APPLICABLE_TYPE_USER, 'test');
		$this->dbConfig->removeApplicable($id, DBConfigService::APPLICABLE_TYPE_USER, 'test');

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEquals([], $mount['applicable']);
	}

	public function testRemoveApplicableGlobal(): void {
		$id = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$this->dbConfig->addApplicable($id, DBConfigService::APPLICABLE_TYPE_GLOBAL, null);
		$this->dbConfig->removeApplicable($id, DBConfigService::APPLICABLE_TYPE_GLOBAL, null);
		$this->dbConfig->addApplicable($id, DBConfigService::APPLICABLE_TYPE_USER, 'test');

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEquals([
			['type' => DBConfigService::APPLICABLE_TYPE_USER, 'value' => 'test', 'mount_id' => $id]
		], $mount['applicable']);
	}

	public function testSetConfig(): void {
		$id = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$this->dbConfig->setConfig($id, 'foo', 'bar');

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEquals(['foo' => 'bar'], $mount['config']);

		$this->dbConfig->setConfig($id, 'foo2', 'bar2');

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEquals(['foo' => 'bar', 'foo2' => 'bar2'], $mount['config']);
	}

	public function testSetConfigOverwrite(): void {
		$id = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$this->dbConfig->setConfig($id, 'foo', 'bar');
		$this->dbConfig->setConfig($id, 'asd', '1');
		$this->dbConfig->setConfig($id, 'foo', 'qwerty');

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEquals(['foo' => 'qwerty', 'asd' => '1'], $mount['config']);
	}

	public function testSetOption(): void {
		$id = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$this->dbConfig->setOption($id, 'foo', 'bar');

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEquals(['foo' => 'bar'], $mount['options']);

		$this->dbConfig->setOption($id, 'foo2', 'bar2');

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEquals(['foo' => 'bar', 'foo2' => 'bar2'], $mount['options']);
	}

	public function testSetOptionOverwrite(): void {
		$id = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$this->dbConfig->setOption($id, 'foo', 'bar');
		$this->dbConfig->setOption($id, 'asd', '1');
		$this->dbConfig->setOption($id, 'foo', 'qwerty');

		$mount = $this->dbConfig->getMountById($id);
		$this->assertEquals(['foo' => 'qwerty', 'asd' => '1'], $mount['options']);
	}

	public function testGetMountsFor(): void {
		$mounts = $this->dbConfig->getMountsFor(DBConfigService::APPLICABLE_TYPE_USER, 'test');
		$this->assertEquals([], $mounts);

		$id = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$this->dbConfig->addApplicable($id, DBConfigService::APPLICABLE_TYPE_USER, 'test');

		$mounts = $this->dbConfig->getMountsFor(DBConfigService::APPLICABLE_TYPE_USER, 'test');
		$this->assertCount(1, $mounts);
		$this->assertEquals($id, $mounts[0]['mount_id']);
		$this->assertEquals([['type' => DBConfigService::APPLICABLE_TYPE_USER, 'value' => 'test', 'mount_id' => $id]], $mounts[0]['applicable']);
	}

	public function testGetAdminMounts(): void {
		$id1 = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$this->addMount('/test2', 'foo2', 'bar2', 100, DBConfigService::MOUNT_TYPE_PERSONAL);

		$mounts = $this->dbConfig->getAdminMounts();
		$this->assertCount(1, $mounts);
		$this->assertEquals($id1, $mounts[0]['mount_id']);
	}

	public function testGetAdminMountsFor(): void {
		$id1 = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$this->addMount('/test2', 'foo2', 'bar2', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$id3 = $this->addMount('/test3', 'foo3', 'bar3', 100, DBConfigService::MOUNT_TYPE_PERSONAL);

		$this->dbConfig->addApplicable($id1, DBConfigService::APPLICABLE_TYPE_USER, 'test');
		$this->dbConfig->addApplicable($id3, DBConfigService::APPLICABLE_TYPE_USER, 'test');

		$mounts = $this->dbConfig->getAdminMountsFor(DBConfigService::APPLICABLE_TYPE_USER, 'test');
		$this->assertCount(1, $mounts);
		$this->assertEquals($id1, $mounts[0]['mount_id']);
		$this->assertEquals([['type' => DBConfigService::APPLICABLE_TYPE_USER, 'value' => 'test', 'mount_id' => $id1]], $mounts[0]['applicable']);
	}

	public function testGetUserMountsFor(): void {
		$id1 = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$this->addMount('/test2', 'foo2', 'bar2', 100, DBConfigService::MOUNT_TYPE_PERSONAL);
		$id3 = $this->addMount('/test3', 'foo3', 'bar3', 100, DBConfigService::MOUNT_TYPE_PERSONAL);

		$this->dbConfig->addApplicable($id1, DBConfigService::APPLICABLE_TYPE_USER, 'test');
		$this->dbConfig->addApplicable($id3, DBConfigService::APPLICABLE_TYPE_USER, 'test');

		$mounts = $this->dbConfig->getUserMountsFor(DBConfigService::APPLICABLE_TYPE_USER, 'test');
		$this->assertCount(1, $mounts);
		$this->assertEquals($id3, $mounts[0]['mount_id']);
		$this->assertEquals([['type' => DBConfigService::APPLICABLE_TYPE_USER, 'value' => 'test', 'mount_id' => $id3]], $mounts[0]['applicable']);
	}

	public function testGetAdminMountsForGlobal(): void {
		$id1 = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);

		$this->dbConfig->addApplicable($id1, DBConfigService::APPLICABLE_TYPE_GLOBAL, null);

		$mounts = $this->dbConfig->getAdminMountsFor(DBConfigService::APPLICABLE_TYPE_GLOBAL, null);
		$this->assertCount(1, $mounts);
		$this->assertEquals($id1, $mounts[0]['mount_id']);
		$this->assertEquals([['type' => DBConfigService::APPLICABLE_TYPE_GLOBAL, 'value' => null, 'mount_id' => $id1]], $mounts[0]['applicable']);
	}

	public function testSetMountPoint(): void {
		$id1 = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$id2 = $this->addMount('/foo', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);

		$this->dbConfig->setMountPoint($id1, '/asd');

		$mount = $this->dbConfig->getMountById($id1);
		$this->assertEquals('/asd', $mount['mount_point']);

		// remains unchanged
		$mount = $this->dbConfig->getMountById($id2);
		$this->assertEquals('/foo', $mount['mount_point']);
	}

	public function testSetAuthBackend(): void {
		$id1 = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$id2 = $this->addMount('/foo', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);

		$this->dbConfig->setAuthBackend($id1, 'none');

		$mount = $this->dbConfig->getMountById($id1);
		$this->assertEquals('none', $mount['auth_backend']);

		// remains unchanged
		$mount = $this->dbConfig->getMountById($id2);
		$this->assertEquals('bar', $mount['auth_backend']);
	}

	public function testGetMountsForDuplicateByGroup(): void {
		$id1 = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);

		$this->dbConfig->addApplicable($id1, DBConfigService::APPLICABLE_TYPE_GROUP, 'group1');
		$this->dbConfig->addApplicable($id1, DBConfigService::APPLICABLE_TYPE_GROUP, 'group2');

		$mounts = $this->dbConfig->getAdminMountsForMultiple(DBConfigService::APPLICABLE_TYPE_GROUP, ['group1', 'group2']);
		$this->assertCount(1, $mounts);
		$this->assertEquals($id1, $mounts[0]['mount_id']);
	}

	public function testGetAllMounts(): void {
		$id1 = $this->addMount('/test', 'foo', 'bar', 100, DBConfigService::MOUNT_TYPE_ADMIN);
		$id2 = $this->addMount('/test2', 'foo2', 'bar2', 100, DBConfigService::MOUNT_TYPE_PERSONAL);

		$mounts = $this->dbConfig->getAllMounts();
		$this->assertCount(2, $mounts);
		$this->assertEquals($id1, $mounts[0]['mount_id']);
		$this->assertEquals($id2, $mounts[1]['mount_id']);
	}
}
