<?php
/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Repair;


use OC\Repair\RepairInvalidShares;
use OC\Share\Constants;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Test\TestCase;

/**
 * Tests for repairing invalid shares
 *
 * @group DB
 *
 * @see \OC\Repair\RepairInvalidShares
 */
class RepairInvalidSharesTest extends TestCase {

	/** @var IRepairStep */
	private $repair;

	/** @var \OCP\IDBConnection */
	private $connection;

	protected function setUp() {
		parent::setUp();

		$config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$config->expects($this->any())
			->method('getSystemValue')
			->with('version')
			->will($this->returnValue('8.0.0.0'));

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->deleteAllShares();

		/** @var \OCP\IConfig $config */
		$this->repair = new RepairInvalidShares($config, $this->connection);
	}

	protected function tearDown() {
		$this->deleteAllShares();

		parent::tearDown();
	}

	protected function deleteAllShares() {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')->execute();
	}

	/**
	 * Test remove expiration date for non-link shares
	 */
	public function testRemoveExpirationDateForNonLinkShares() {
		// user share with bogus expiration date
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(Constants::SHARE_TYPE_USER),
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
			])
			->execute();

		$bogusShareId = $this->getLastShareId();

		// link share with expiration date
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(Constants::SHARE_TYPE_LINK),
				'uid_owner' => $qb->expr()->literal('user1'),
				'item_type' => $qb->expr()->literal('folder'),
				'item_source' => $qb->expr()->literal(123),
				'item_target' => $qb->expr()->literal('/123'),
				'file_source' => $qb->expr()->literal(123),
				'file_target' => $qb->expr()->literal('/test'),
				'permissions' => $qb->expr()->literal(1),
				'stime' => $qb->expr()->literal(time()),
				'expiration' => $qb->expr()->literal('2015-09-25 00:00:00'),
				'token' => $qb->expr()->literal('abcdefg')
			])->execute();

		/** @var IOutput | \PHPUnit_Framework_MockObject_MockObject $outputMock */
		$outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$this->repair->run($outputMock);

		$results = $this->connection->getQueryBuilder()
			->select('*')
			->from('share')
			->orderBy('share_type', 'ASC')
			->execute()
			->fetchAll();

		$this->assertCount(2, $results);

		$userShare = $results[0];
		$linkShare = $results[1];
		$this->assertEquals($bogusShareId, $userShare['id'], 'sanity check');
		$this->assertNull($userShare['expiration'], 'bogus expiration date was removed');
		$this->assertNotNull($linkShare['expiration'], 'valid link share expiration date still there');
	}

	/**
	 * Test remove expiration date for non-link shares
	 */
	public function testAddShareLinkDeletePermission() {
		$oldPerms = \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE;
		$newPerms = \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_DELETE;

		// share with old permissions
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(Constants::SHARE_TYPE_LINK),
				'uid_owner' => $qb->expr()->literal('user1'),
				'item_type' => $qb->expr()->literal('folder'),
				'item_source' => $qb->expr()->literal(123),
				'item_target' => $qb->expr()->literal('/123'),
				'file_source' => $qb->expr()->literal(123),
				'file_target' => $qb->expr()->literal('/test'),
				'permissions' => $qb->expr()->literal($oldPerms),
				'stime' => $qb->expr()->literal(time()),
			])
			->execute();

		$bogusShareId = $this->getLastShareId();

		// share with read-only permissions
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(Constants::SHARE_TYPE_LINK),
				'uid_owner' => $qb->expr()->literal('user1'),
				'item_type' => $qb->expr()->literal('folder'),
				'item_source' => $qb->expr()->literal(123),
				'item_target' => $qb->expr()->literal('/123'),
				'file_source' => $qb->expr()->literal(123),
				'file_target' => $qb->expr()->literal('/test'),
				'permissions' => $qb->expr()->literal(\OCP\Constants::PERMISSION_READ),
				'stime' => $qb->expr()->literal(time()),
			])
			->execute();

		$keepThisShareId = $this->getLastShareId();

		// user share to keep
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(Constants::SHARE_TYPE_USER),
				'share_with' => $qb->expr()->literal('recipientuser1'),
				'uid_owner' => $qb->expr()->literal('user1'),
				'item_type' => $qb->expr()->literal('folder'),
				'item_source' => $qb->expr()->literal(123),
				'item_target' => $qb->expr()->literal('/123'),
				'file_source' => $qb->expr()->literal(123),
				'file_target' => $qb->expr()->literal('/test'),
				'permissions' => $qb->expr()->literal(3),
				'stime' => $qb->expr()->literal(time()),
			])
			->execute();

		$keepThisShareId2 = $this->getLastShareId();

		/** @var IOutput | \PHPUnit_Framework_MockObject_MockObject $outputMock */
		$outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$this->repair->run($outputMock);

		$results = $this->connection->getQueryBuilder()
			->select('*')
			->from('share')
			->orderBy('permissions', 'ASC')
			->execute()
			->fetchAll();

		$this->assertCount(3, $results);

		$untouchedShare = $results[0];
		$untouchedShare2 = $results[1];
		$updatedShare = $results[2];
		$this->assertEquals($keepThisShareId, $untouchedShare['id'], 'sanity check');
		$this->assertEquals($keepThisShareId2, $untouchedShare2['id'], 'sanity check');
		$this->assertEquals($bogusShareId, $updatedShare['id'], 'sanity check');
		$this->assertEquals($newPerms, $updatedShare['permissions'], 'delete permission was added');
	}

	/**
	 * Test remove shares where the parent share does not exist anymore
	 */
	public function testSharesNonExistingParent() {
		$qb = $this->connection->getQueryBuilder();
		$shareValues = [
			'share_type' => $qb->expr()->literal(Constants::SHARE_TYPE_USER),
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
			->execute();
		$parent = $this->getLastShareId();

		// share with existing parent
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values(array_merge($shareValues, [
				'parent' => $qb->expr()->literal($parent),
			]))->execute();
		$validChild = $this->getLastShareId();

		// share with non-existing parent
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values(array_merge($shareValues, [
				'parent' => $qb->expr()->literal($parent + 100),
			]))->execute();
		$invalidChild = $this->getLastShareId();

		$query = $this->connection->getQueryBuilder();
		$result = $query->select('id')
			->from('share')
			->orderBy('id', 'ASC')
			->execute();
		$rows = $result->fetchAll();
		$this->assertEquals([['id' => $parent], ['id' => $validChild], ['id' => $invalidChild]], $rows);
		$result->closeCursor();

		/** @var IOutput | \PHPUnit_Framework_MockObject_MockObject $outputMock */
		$outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$this->repair->run($outputMock);

		$query = $this->connection->getQueryBuilder();
		$result = $query->select('id')
			->from('share')
			->orderBy('id', 'ASC')
			->execute();
		$rows = $result->fetchAll();
		$this->assertEquals([['id' => $parent], ['id' => $validChild]], $rows);
		$result->closeCursor();
	}

	/**
	 * @return int
	 */
	protected function getLastShareId() {
		return $this->connection->lastInsertId('*PREFIX*share');
	}
}

