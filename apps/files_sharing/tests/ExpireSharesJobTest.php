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
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class ExpireSharesJobTest
 *
 *
 * @package OCA\Files_Sharing\Tests
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ExpireSharesJobTest extends \Test\TestCase {

	private ExpireSharesJob $job;

	private IDBConnection $connection;
	private IRootFolder $rootFolder;

	private IUser $user1;

	private IUser $user2;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->rootFolder = Server::get(IRootFolder::class);
		// clear occasional leftover shares from other tests
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')->executeStatement();

		$user1 = $this->getUniqueID('user1_');
		$user2 = $this->getUniqueID('user2_');

		$userManager = Server::get(IUserManager::class);
		$this->user1 = $userManager->createUser($user1, 'longrandompassword');
		$this->user2 = $userManager->createUser($user2, 'longrandompassword');

		\OC::registerShareHooks(Server::get(SystemConfig::class));

		$this->job = new ExpireSharesJob(Server::get(ITimeFactory::class), Server::get(IManager::class), $this->connection);
	}

	protected function tearDown(): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')->executeStatement();

		$this->user1->delete();
		$this->user2->delete();

		$this->logout();

		parent::tearDown();
	}

	private function getShares(): array {
		$shares = [];
		$qb = $this->connection->getQueryBuilder();

		$result = $qb->select('*')
			->from('share')
			->executeQuery();

		while ($row = $result->fetchAssociative()) {
			$shares[] = $row;
		}
		$result->closeCursor();
		return $shares;
	}

	public static function dataExpireLinkShare() {
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
	 * @param bool addExpiration Should we add an expire date
	 * @param string $interval The dateInterval
	 * @param bool $addInterval If true add to the current time if false subtract
	 * @param bool $shouldExpire Should this share be expired
	 */
	#[DataProvider(methodName: 'dataExpireLinkShare')]
	public function testExpireLinkShare(bool $addExpiration, string $interval, bool $addInterval, bool $shouldExpire): void {
		$this->loginAsUser($this->user1->getUID());

		$user1Folder = $this->rootFolder->getUserFolder($this->user1->getUID());
		$testFolder = $user1Folder->newFolder('test');

		$shareManager = Server::get(IManager::class);
		$share = $shareManager->newShare();

		$share->setNode($testFolder)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(Constants::PERMISSION_READ)
			->setSharedBy($this->user1->getUID());

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
				->executeStatement();

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
		$this->loginAsUser($this->user1->getUID());

		$user1Folder = $this->rootFolder->getUserFolder($this->user1->getUID());
		$testFolder = $user1Folder->newFolder('test');

		$shareManager = Server::get(IManager::class);
		$share = $shareManager->newShare();

		$share->setNode($testFolder)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(Constants::PERMISSION_READ)
			->setSharedBy($this->user1->getUID())
			->setSharedWith($this->user2->getUID());

		$shareManager->createShare($share);

		$shares = $this->getShares();
		$this->assertCount(1, $shares);

		$this->logout();

		$this->job->run([]);

		$shares = $this->getShares();
		$this->assertCount(1, $shares);
	}
}
