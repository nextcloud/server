<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Repair\RepairInvalidShares;
use OCP\Constants;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Server;
use OCP\Share\IShare;
use Test\TestCase;

/**
 * Tests for repairing invalid shares
 *
 * @group DB
 *
 * @see \OC\Repair\RepairInvalidShares
 */
class RepairInvalidSharesTest extends TestCase {

	private RepairInvalidShares $repair;
	private IDBConnection $connection;

	protected function setUp(): void {
		parent::setUp();

		$config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$config->expects($this->any())
			->method('getSystemValueString')
			->with('version')
			->willReturn('12.0.0.0');

		$this->connection = Server::get(IDBConnection::class);
		$this->deleteAllShares();

		$this->repair = new RepairInvalidShares($config, $this->connection);
	}

	protected function tearDown(): void {
		$this->deleteAllShares();

		parent::tearDown();
	}

	protected function deleteAllShares() {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')->executeStatement();
	}

	/**
	 * Test remove shares where the parent share does not exist anymore
	 */
	public function testSharesNonExistingParent(): void {
		$qb = $this->connection->getQueryBuilder();
		$shareValues = [
			'share_type' => $qb->expr()->literal(IShare::TYPE_USER),
			'share_with' => $qb->expr()->literal('recipientuser1'),
			'uid_owner' => $qb->expr()->literal('user1'),
			'item_type' => $qb->expr()->literal('folder'),
			'item_source' => $qb->expr()->literal(123),
			'item_target' => $qb->expr()->literal('/123'),
			'file_source' => $qb->expr()->literal(123),
			'file_target' => $qb->expr()->literal('/test'),
			'permissions' => $qb->expr()->literal(1),
			'stime' => $qb->expr()->literal(time()),
			'expiration' => $qb->expr()->literal('2015-09-25 00:00:00')
		];

		// valid share
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values($shareValues)
			->executeStatement();
		$parent = $qb->getLastInsertId();

		// share with existing parent
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values(array_merge($shareValues, [
				'parent' => $qb->expr()->literal($parent),
			]))->executeStatement();
		$validChild = $qb->getLastInsertId();

		// share with non-existing parent
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values(array_merge($shareValues, [
				'parent' => $qb->expr()->literal($parent + 100),
			]))->executeStatement();
		$invalidChild = $qb->getLastInsertId();

		$query = $this->connection->getQueryBuilder();
		$result = $query->select('id')
			->from('share')
			->orderBy('id', 'ASC')
			->executeQuery();
		$rows = $result->fetchAll();
		$this->assertEquals([['id' => $parent], ['id' => $validChild], ['id' => $invalidChild]], $rows);
		$result->closeCursor();

		/** @var IOutput | \PHPUnit\Framework\MockObject\MockObject $outputMock */
		$outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$this->repair->run($outputMock);

		$query = $this->connection->getQueryBuilder();
		$result = $query->select('id')
			->from('share')
			->orderBy('id', 'ASC')
			->executeQuery();
		$rows = $result->fetchAll();
		$this->assertEquals([['id' => $parent], ['id' => $validChild]], $rows);
		$result->closeCursor();
	}

	public static function fileSharePermissionsProvider(): array {
		return [
			// unchanged for folder
			[
				'folder',
				31,
				31,
			],
			// unchanged for read-write + share
			[
				'file',
				Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE,
				Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE,
			],
			// fixed for all perms
			[
				'file',
				Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE | Constants::PERMISSION_SHARE,
				Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE,
			],
		];
	}

	/**
	 * Test adjusting file share permissions
	 *
	 * @dataProvider fileSharePermissionsProvider
	 */
	public function testFileSharePermissions($itemType, $testPerms, $expectedPerms): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(IShare::TYPE_LINK),
				'uid_owner' => $qb->expr()->literal('user1'),
				'item_type' => $qb->expr()->literal($itemType),
				'item_source' => $qb->expr()->literal(123),
				'item_target' => $qb->expr()->literal('/123'),
				'file_source' => $qb->expr()->literal(123),
				'file_target' => $qb->expr()->literal('/test'),
				'permissions' => $qb->expr()->literal($testPerms),
				'stime' => $qb->expr()->literal(time()),
			])
			->executeStatement();

		/** @var IOutput | \PHPUnit\Framework\MockObject\MockObject $outputMock */
		$outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$this->repair->run($outputMock);

		$results = $this->connection->getQueryBuilder()
			->select('*')
			->from('share')
			->orderBy('permissions', 'ASC')
			->executeQuery()
			->fetchAll();

		$this->assertCount(1, $results);

		$updatedShare = $results[0];

		$this->assertEquals($expectedPerms, $updatedShare['permissions']);
	}
}
