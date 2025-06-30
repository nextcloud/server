<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Repair\OldGroupMembershipShares;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Server;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class OldGroupMembershipSharesTest
 *
 * @group DB
 *
 * @package Test\Repair
 */
class OldGroupMembershipSharesTest extends \Test\TestCase {

	private IDBConnection $connection;
	private IGroupManager&MockObject $groupManager;

	protected function setUp(): void {
		parent::setUp();

		$this->groupManager = $this->getMockBuilder(IGroupManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->connection = Server::get(IDBConnection::class);

		$this->deleteAllShares();
	}

	protected function tearDown(): void {
		$this->deleteAllShares();

		parent::tearDown();
	}

	protected function deleteAllShares() {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')->executeStatement();
	}

	public function testRun(): void {
		$repair = new OldGroupMembershipShares(
			$this->connection,
			$this->groupManager
		);

		$this->groupManager->expects($this->exactly(2))
			->method('isInGroup')
			->willReturnMap([
				['member', 'group', true],
				['not-a-member', 'group', false],
			]);

		$parent = $this->createShare(IShare::TYPE_GROUP, 'group', null);
		$group2 = $this->createShare(IShare::TYPE_GROUP, 'group2', $parent);
		$user1 = $this->createShare(IShare::TYPE_USER, 'user1', $parent);

		// \OC\Share\Constant::$shareTypeGroupUserUnique === 2
		$member = $this->createShare(2, 'member', $parent);
		$notAMember = $this->createShare(2, 'not-a-member', $parent);

		$query = $this->connection->getQueryBuilder();
		$result = $query->select('id')
			->from('share')
			->orderBy('id', 'ASC')
			->executeQuery();
		$rows = $result->fetchAll();
		$this->assertEquals([['id' => $parent], ['id' => $group2], ['id' => $user1], ['id' => $member], ['id' => $notAMember]], $rows);
		$result->closeCursor();

		/** @var IOutput | \PHPUnit\Framework\MockObject\MockObject $outputMock */
		$outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$repair->run($outputMock);

		$query = $this->connection->getQueryBuilder();
		$result = $query->select('id')
			->from('share')
			->orderBy('id', 'ASC')
			->executeQuery();
		$rows = $result->fetchAll();
		$this->assertEquals([['id' => $parent], ['id' => $group2], ['id' => $user1], ['id' => $member]], $rows);
		$result->closeCursor();
	}

	/**
	 * @param string $shareType
	 * @param string $shareWith
	 * @param null|int $parent
	 * @return int
	 */
	protected function createShare($shareType, $shareWith, $parent) {
		$qb = $this->connection->getQueryBuilder();
		$shareValues = [
			'share_type' => $qb->expr()->literal($shareType),
			'share_with' => $qb->expr()->literal($shareWith),
			'uid_owner' => $qb->expr()->literal('user1'),
			'item_type' => $qb->expr()->literal('folder'),
			'item_source' => $qb->expr()->literal(123),
			'item_target' => $qb->expr()->literal('/123'),
			'file_source' => $qb->expr()->literal(123),
			'file_target' => $qb->expr()->literal('/test'),
			'permissions' => $qb->expr()->literal(1),
			'stime' => $qb->expr()->literal(time()),
			'expiration' => $qb->expr()->literal('2015-09-25 00:00:00'),
		];

		if ($parent) {
			$shareValues['parent'] = $qb->expr()->literal($parent);
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values($shareValues)
			->executeStatement();

		return $qb->getLastInsertId();
	}
}
