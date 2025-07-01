<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\SystemConfig;
use OCA\Files_Sharing\ExpireSharesJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Constants;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Share\IShare;

/**
 * Class ExpireSharesJobTest
 *
 * @group DB
 *
 * @package OCA\Files_Sharing\Tests
 */
class ExpireSharesJobTest extends \Test\TestCase {

	/** @var ExpireSharesJob */
	private $job;

	/** @var IDBConnection */
	private $connection;

	/** @var string */
	private $user1;

	/** @var string */
	private $user2;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		// clear occasional leftover shares from other tests
		$this->connection->executeUpdate('DELETE FROM `*PREFIX*share`');

		$this->user1 = $this->getUniqueID('user1_');
		$this->user2 = $this->getUniqueID('user2_');

		$userManager = Server::get(IUserManager::class);
		$userManager->createUser($this->user1, 'longrandompassword');
		$userManager->createUser($this->user2, 'longrandompassword');

		\OC::registerShareHooks(Server::get(SystemConfig::class));

		$this->job = new ExpireSharesJob(Server::get(ITimeFactory::class), Server::get(IManager::class), $this->connection);
	}

	protected function tearDown(): void {
		$this->connection->executeUpdate('DELETE FROM `*PREFIX*share`');

		$userManager = Server::get(IUserManager::class);
		$user1 = $userManager->get($this->user1);
		if ($user1) {
			$user1->delete();
		}
		$user2 = $userManager->get($this->user2);
		if ($user2) {
			$user2->delete();
		}

		$this->logout();

		parent::tearDown();
	}

	private function getShares() {
		$shares = [];
		$qb = $this->connection->getQueryBuilder();

		$result = $qb->select('*')
			->from('share')
			->execute();

		while ($row = $result->fetch()) {
			$shares[] = $row;
		}
		$result->closeCursor();
		return $shares;
	}

	public function dataExpireLinkShare() {
		return [
			[false,   '', false, false],
			[false,   '',  true, false],
			[true, 'P1D', false,  true],
			[true, 'P1D',  true, false],
			[true, 'P1W', false,  true],
			[true, 'P1W',  true, false],
			[true, 'P1M', false,  true],
			[true, 'P1M',  true, false],
			[true, 'P1Y', false,  true],
			[true, 'P1Y',  true, false],
		];
	}

	/**
	 * @dataProvider dataExpireLinkShare
	 *
	 * @param bool addExpiration Should we add an expire date
	 * @param string $interval The dateInterval
	 * @param bool $addInterval If true add to the current time if false subtract
	 * @param bool $shouldExpire Should this share be expired
	 */
	public function testExpireLinkShare($addExpiration, $interval, $addInterval, $shouldExpire): void {
		$this->loginAsUser($this->user1);

		$user1Folder = \OC::$server->getUserFolder($this->user1);
		$testFolder = $user1Folder->newFolder('test');

		$shareManager = Server::get(\OCP\Share\IManager::class);
		$share = $shareManager->newShare();

		$share->setNode($testFolder)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(Constants::PERMISSION_READ)
			->setSharedBy($this->user1);

		$shareManager->createShare($share);

		$shares = $this->getShares();
		$this->assertCount(1, $shares);
		reset($shares);
		$share = current($shares);

		if ($addExpiration) {
			$expire = new \DateTime();
			$expire->setTime(0, 0, 0);
			if ($addInterval) {
				$expire->add(new \DateInterval($interval));
			} else {
				$expire->sub(new \DateInterval($interval));
			}
			$expire = $expire->format('Y-m-d 00:00:00');

			// Set expiration date to yesterday
			$qb = $this->connection->getQueryBuilder();
			$qb->update('share')
				->set('expiration', $qb->createParameter('expiration'))
				->where($qb->expr()->eq('id', $qb->createParameter('id')))
				->setParameter('id', $share['id'])
				->setParameter('expiration', $expire)
				->execute();

			$shares = $this->getShares();
			$this->assertCount(1, $shares);
		}

		$this->logout();

		$this->job->run([]);

		$shares = $this->getShares();

		if ($shouldExpire) {
			$this->assertCount(0, $shares);
		} else {
			$this->assertCount(1, $shares);
		}
	}

	public function testDoNotExpireOtherShares(): void {
		$this->loginAsUser($this->user1);

		$user1Folder = \OC::$server->getUserFolder($this->user1);
		$testFolder = $user1Folder->newFolder('test');

		$shareManager = Server::get(\OCP\Share\IManager::class);
		$share = $shareManager->newShare();

		$share->setNode($testFolder)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(Constants::PERMISSION_READ)
			->setSharedBy($this->user1)
			->setSharedWith($this->user2);

		$shareManager->createShare($share);

		$shares = $this->getShares();
		$this->assertCount(1, $shares);

		$this->logout();

		$this->job->run([]);

		$shares = $this->getShares();
		$this->assertCount(1, $shares);
	}
}
