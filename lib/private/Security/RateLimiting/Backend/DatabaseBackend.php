<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\RateLimiting\Backend;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;

class DatabaseBackend implements IBackend {
	private const TABLE_NAME = 'ratelimit_entries';

	public function __construct(
		private IConfig $config,
		private IDBConnection $dbConnection,
		private ITimeFactory $timeFactory,
	) {
	}

	private function hash(
		string $methodIdentifier,
		string $userIdentifier,
	): string {
		return hash('sha512', $methodIdentifier . $userIdentifier);
	}

	/**
	 * @throws Exception
	 */
	private function getExistingAttemptCount(
		string $identifier,
	): int {
		$currentTime = $this->timeFactory->getDateTime();

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_NAME)
			->where(
				$qb->expr()->lte('delete_after', $qb->createNamedParameter($currentTime, IQueryBuilder::PARAM_DATETIME_MUTABLE))
			)
			->executeStatement();

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select($qb->func()->count())
			->from(self::TABLE_NAME)
			->where(
				$qb->expr()->eq('hash', $qb->createNamedParameter($identifier, IQueryBuilder::PARAM_STR))
			);

		$cursor = $qb->executeQuery();
		$row = $cursor->fetchOne();
		$cursor->closeCursor();

		return (int)$row;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAttempts(
		string $methodIdentifier,
		string $userIdentifier,
	): int {
		$identifier = $this->hash($methodIdentifier, $userIdentifier);
		return $this->getExistingAttemptCount($identifier);
	}

	/**
	 * {@inheritDoc}
	 */
	public function registerAttempt(
		string $methodIdentifier,
		string $userIdentifier,
		int $period,
	): void {
		$identifier = $this->hash($methodIdentifier, $userIdentifier);
		$deleteAfter = $this->timeFactory->getDateTime()->add(new \DateInterval("PT{$period}S"));

		$qb = $this->dbConnection->getQueryBuilder();

		$qb->insert(self::TABLE_NAME)
			->values([
				'hash' => $qb->createNamedParameter($identifier, IQueryBuilder::PARAM_STR),
				'delete_after' => $qb->createNamedParameter($deleteAfter, IQueryBuilder::PARAM_DATETIME_MUTABLE),
			]);

		if (!$this->config->getSystemValueBool('ratelimit.protection.enabled', true)) {
			return;
		}

		$qb->executeStatement();
	}
}
