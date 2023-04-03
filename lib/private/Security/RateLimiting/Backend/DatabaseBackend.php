<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2021 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Security\RateLimiting\Backend;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;

class DatabaseBackend implements IBackend {
	private const TABLE_NAME = 'ratelimit_entries';

	/** @var IConfig */
	private $config;
	/** @var IDBConnection */
	private $dbConnection;
	/** @var ITimeFactory */
	private $timeFactory;

	public function __construct(
		IConfig $config,
		IDBConnection $dbConnection,
		ITimeFactory $timeFactory
	) {
		$this->config = $config;
		$this->dbConnection = $dbConnection;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param string $methodIdentifier
	 * @param string $userIdentifier
	 * @return string
	 */
	private function hash(string $methodIdentifier,
						  string $userIdentifier): string {
		return hash('sha512', $methodIdentifier . $userIdentifier);
	}

	/**
	 * @param string $identifier
	 * @param int $seconds
	 * @return int
	 * @throws \OCP\DB\Exception
	 */
	private function getExistingAttemptCount(
		string $identifier
	): int {
		$currentTime = $this->timeFactory->getDateTime();

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_NAME)
			->where(
				$qb->expr()->lte('delete_after', $qb->createNamedParameter($currentTime, IQueryBuilder::PARAM_DATE))
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
	public function getAttempts(string $methodIdentifier,
								string $userIdentifier): int {
		$identifier = $this->hash($methodIdentifier, $userIdentifier);
		return $this->getExistingAttemptCount($identifier);
	}

	/**
	 * {@inheritDoc}
	 */
	public function registerAttempt(string $methodIdentifier,
									string $userIdentifier,
									int $period) {
		$identifier = $this->hash($methodIdentifier, $userIdentifier);
		$deleteAfter = $this->timeFactory->getDateTime()->add(new \DateInterval("PT{$period}S"));

		$qb = $this->dbConnection->getQueryBuilder();

		$qb->insert(self::TABLE_NAME)
			->values([
				'hash' => $qb->createNamedParameter($identifier, IQueryBuilder::PARAM_STR),
				'delete_after' => $qb->createNamedParameter($deleteAfter, IQueryBuilder::PARAM_DATE),
			]);

		if (!$this->config->getSystemValueBool('ratelimit.protection.enabled', true)) {
			return;
		}

		$qb->executeStatement();
	}
}
