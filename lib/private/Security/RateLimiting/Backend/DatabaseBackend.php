<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Lukas Reschke <lukas@statuscode.ch>
 *
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
use OCP\IDBConnection;

/**
 * Class DatabaseBackend uses the database for storing rate limiting data.
 *
 * @package OC\Security\RateLimiting\Backend
 */
class DatabaseBackend implements IBackend {
	private const TABLE_NAME = 'ratelimit_entries';

	/** @var IDBConnection */
	private $dbConnection;
	/** @var ITimeFactory */
	private $timeFactory;

	/**
	 * @param IDBConnection $dbConnection
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(
		IDBConnection $dbConnection,
		ITimeFactory $timeFactory
	) {
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
		string $identifier,
		int $seconds
	): int {
		$qb = $this->dbConnection->getQueryBuilder();
		$notOlderThan = $this->timeFactory->getDateTime()->sub(new \DateInterval("PT{$seconds}S"));

		$qb->selectAlias($qb->createFunction('COUNT(*)'), 'count')
			->from(self::TABLE_NAME)
			->where(
				$qb->expr()->eq('hash', $qb->createNamedParameter($identifier, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->gte('timestamp', $qb->createParameter('notOlderThan'))
			)
			->setParameter('notOlderThan', $notOlderThan, 'datetime');

		$cursor = $qb->executeQuery();
		$row = $cursor->fetch();
		$cursor->closeCursor();

		return (int)$row['count'];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAttempts(string $methodIdentifier,
								string $userIdentifier,
								int $seconds): int {
		$identifier = $this->hash($methodIdentifier, $userIdentifier);
		return $this->getExistingAttemptCount($identifier, $seconds);
	}

	/**
	 * {@inheritDoc}
	 */
	public function registerAttempt(string $methodIdentifier,
									string $userIdentifier,
									int $period) {
		$identifier = $this->hash($methodIdentifier, $userIdentifier);
		$currentTime = $this->timeFactory->getDateTime();
		$notOlderThan = $this->timeFactory->getDateTime('@' . $period);

		$qb = $this->dbConnection->getQueryBuilder();

		$qb->delete(self::TABLE_NAME)
			->where(
				$qb->expr()->eq('hash', $qb->createNamedParameter($identifier, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->lt('timestamp', $qb->createParameter('notOlderThan'))
			)
			->setParameter('notOlderThan', $notOlderThan, 'datetime')
			->executeStatement();

		$qb->insert(self::TABLE_NAME)
			->values([
				'hash' => $qb->createNamedParameter($identifier, IQueryBuilder::PARAM_STR),
				'timestamp' => $qb->createNamedParameter($currentTime, IQueryBuilder::PARAM_DATE),
			])
			->executeStatement();
	}
}
