<?php
/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace Test\Repair;

/**
 * Tests for repairing invalid shares
 *
 * @see \OC\Repair\RepairInvalidShares
 */
class RepairInvalidShares extends \Test\TestCase {

	/** @var \OC\RepairStep */
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

		$this->repair = new \OC\Repair\RepairInvalidShares($config, $this->connection);
	}

	protected function tearDown() {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')->execute();

		parent::tearDown();
	}

	/**
	 * Test remove expiration date for non-link shares
	 */
	public function testRemoveExpirationDateForNonLinkShares() {
		// user share with bogus expiration date
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(\OC\Share\Constants::SHARE_TYPE_USER),
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

		// select because lastInsertId does not work with OCI
		$results = $this->connection->getQueryBuilder()
			->select('id')
			->from('share')
			->execute()
			->fetchAll();
		$bogusShareId = $results[0]['id'];

		// link share with expiration date
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(\OC\Share\Constants::SHARE_TYPE_LINK),
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

		$this->repair->run();

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
}

