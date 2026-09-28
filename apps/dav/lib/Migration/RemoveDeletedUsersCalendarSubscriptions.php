<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCP\DB\Exception;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RemoveDeletedUsersCalendarSubscriptions implements IRepairStep {
	/** @var int */
	private $progress = 0;

	/** @var int[] */
	private $orphanSubscriptionIds = [];

	private const SUBSCRIPTIONS_CHUNK_SIZE = 1000;

	public function __construct(
		private IDBConnection $connection,
		private IUserManager $userManager,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function getName(): string {
		return 'Clean up old calendar subscriptions from deleted users that were not cleaned-up';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {
		$nbSubscriptions = $this->countSubscriptions();

		$output->startProgress($nbSubscriptions);

		while ($this->progress < $nbSubscriptions) {
			$this->checkSubscriptions();

			$this->progress += self::SUBSCRIPTIONS_CHUNK_SIZE;
			$output->advance(min(self::SUBSCRIPTIONS_CHUNK_SIZE, $nbSubscriptions));
		}
		$output->finishProgress();
		$this->deleteOrphanSubscriptions();

		$output->info(sprintf('%d calendar subscriptions without an user have been cleaned up', count($this->orphanSubscriptionIds)));
	}

	/**
	 * @throws Exception
	 */
	private function countSubscriptions(): int {
		$qb = $this->connection->getQueryBuilder();
		$query = $qb->select($qb->func()->count('*'))
			->from('calendarsubscriptions');

		$result = $query->execute();
		$count = $result->fetchOne();
		$result->closeCursor();

		if ($count !== false) {
			$count = (int)$count;
		} else {
			$count = 0;
		}

		return $count;
	}

	/**
	 * @throws Exception
	 */
	private function checkSubscriptions(): void {
		$qb = $this->connection->getQueryBuilder();
		$query = $qb->selectDistinct(['id', 'principaluri'])
			->from('calendarsubscriptions')
			->setMaxResults(self::SUBSCRIPTIONS_CHUNK_SIZE)
			->setFirstResult($this->progress);

		$result = $query->execute();
		while ($row = $result->fetch()) {
			$username = $this->getPrincipal($row['principaluri']);
			if (!$this->userManager->userExists($username)) {
				$this->orphanSubscriptionIds[] = (int)$row['id'];
			}
		}
		$result->closeCursor();
	}

	/**
	 * @throws Exception
	 */
	private function deleteOrphanSubscriptions(): void {
		foreach ($this->orphanSubscriptionIds as $orphanSubscriptionID) {
			$this->deleteOrphanSubscription($orphanSubscriptionID);
		}
	}

	/**
	 * @throws Exception
	 */
	private function deleteOrphanSubscription(int $orphanSubscriptionID): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('calendarsubscriptions')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($orphanSubscriptionID)))
			->executeStatement();
	}

	private function getPrincipal(string $principalUri): string {
		$uri = explode('/', $principalUri);
		return array_pop($uri);
	}
}
