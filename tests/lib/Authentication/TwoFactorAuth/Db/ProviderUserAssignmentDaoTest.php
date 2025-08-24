<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\TwoFactorAuth\Db;

use OC\Authentication\TwoFactorAuth\Db\ProviderUserAssignmentDao;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class ProviderUserAssignmentDaoTest extends TestCase {
	/** @var IDBConnection */
	private $dbConn;

	/** @var ProviderUserAssignmentDao */
	private $dao;

	protected function setUp(): void {
		parent::setUp();

		$this->dbConn = Server::get(IDBConnection::class);
		$qb = $this->dbConn->getQueryBuilder();
		$q = $qb->delete(ProviderUserAssignmentDao::TABLE_NAME);
		$q->execute();

		$this->dao = new ProviderUserAssignmentDao($this->dbConn);
	}

	public function testGetState(): void {
		$qb = $this->dbConn->getQueryBuilder();
		$q1 = $qb->insert(ProviderUserAssignmentDao::TABLE_NAME)->values([
			'provider_id' => $qb->createNamedParameter('twofactor_u2f'),
			'uid' => $qb->createNamedParameter('user123'),
			'enabled' => $qb->createNamedParameter(1),
		]);
		$q1->execute();
		$q2 = $qb->insert(ProviderUserAssignmentDao::TABLE_NAME)->values([
			'provider_id' => $qb->createNamedParameter('twofactor_totp'),
			'uid' => $qb->createNamedParameter('user123'),
			'enabled' => $qb->createNamedParameter(0),
		]);
		$q2->execute();
		$expected = [
			'twofactor_u2f' => true,
			'twofactor_totp' => false,
		];

		$state = $this->dao->getState('user123');

		$this->assertEquals($expected, $state);
	}

	public function testPersist(): void {
		$qb = $this->dbConn->getQueryBuilder();

		$this->dao->persist('twofactor_totp', 'user123', 0);

		$q = $qb
			->select('*')
			->from(ProviderUserAssignmentDao::TABLE_NAME)
			->where($qb->expr()->eq('provider_id', $qb->createNamedParameter('twofactor_totp')))
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter('user123')))
			->andWhere($qb->expr()->eq('enabled', $qb->createNamedParameter(0)));
		$res = $q->execute();
		$data = $res->fetchAll();
		$res->closeCursor();
		$this->assertCount(1, $data);
	}

	public function testPersistTwice(): void {
		$qb = $this->dbConn->getQueryBuilder();

		$this->dao->persist('twofactor_totp', 'user123', 0);
		$this->dao->persist('twofactor_totp', 'user123', 1);

		$q = $qb
			->select('*')
			->from(ProviderUserAssignmentDao::TABLE_NAME)
			->where($qb->expr()->eq('provider_id', $qb->createNamedParameter('twofactor_totp')))
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter('user123')))
			->andWhere($qb->expr()->eq('enabled', $qb->createNamedParameter(1)));
		$res = $q->execute();
		$data = $res->fetchAll();
		$res->closeCursor();

		$this->assertCount(1, $data);
	}

	public function testPersistSameStateTwice(): void {
		$qb = $this->dbConn->getQueryBuilder();

		$this->dao->persist('twofactor_totp', 'user123', 1);
		$this->dao->persist('twofactor_totp', 'user123', 1);

		$q = $qb
			->select('*')
			->from(ProviderUserAssignmentDao::TABLE_NAME)
			->where($qb->expr()->eq('provider_id', $qb->createNamedParameter('twofactor_totp')))
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter('user123')))
			->andWhere($qb->expr()->eq('enabled', $qb->createNamedParameter(1)));
		$res = $q->execute();
		$data = $res->fetchAll();
		$res->closeCursor();

		$this->assertCount(1, $data);
	}

	public function testDeleteByUser(): void {
		$this->dao->persist('twofactor_fail', 'user1', 1);
		$this->dao->persist('twofactor_u2f', 'user1', 1);
		$this->dao->persist('twofactor_fail', 'user2', 0);
		$this->dao->persist('twofactor_u2f', 'user2', 0);

		$deleted = $this->dao->deleteByUser('user1');

		$this->assertEquals(
			[
				[
					'uid' => 'user1',
					'provider_id' => 'twofactor_fail',
					'enabled' => true,
				],
				[
					'uid' => 'user1',
					'provider_id' => 'twofactor_u2f',
					'enabled' => true,
				],
			],
			$deleted
		);
		$statesUser1 = $this->dao->getState('user1');
		$statesUser2 = $this->dao->getState('user2');
		$this->assertCount(0, $statesUser1);
		$this->assertCount(2, $statesUser2);
	}

	public function testDeleteAll(): void {
		$this->dao->persist('twofactor_fail', 'user1', 1);
		$this->dao->persist('twofactor_u2f', 'user1', 1);
		$this->dao->persist('twofactor_fail', 'user2', 0);
		$this->dao->persist('twofactor_u2f', 'user1', 0);

		$this->dao->deleteAll('twofactor_fail');

		$statesUser1 = $this->dao->getState('user1');
		$statesUser2 = $this->dao->getState('user2');
		$this->assertCount(1, $statesUser1);
		$this->assertCount(0, $statesUser2);
	}
}
