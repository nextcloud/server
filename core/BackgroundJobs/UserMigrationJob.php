<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use Exception;
use NCU\User\UserStatus;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IServerInfo;
use OCP\Snowflake\ISnowflakeGenerator;
use Override;
use Psr\Log\LoggerInterface;

class UserMigrationJob extends TimedJob {
	private const BATCH_SIZE = 200;

	public function __construct(
		ITimeFactory $time,
		private readonly IAppConfig $appConfig,
		private readonly IJobList $jobList,
		private readonly IServerInfo $serverInfo,
		private readonly IDBConnection $connection,
		private readonly ISnowflakeGenerator $snowflakeGenerator,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->setInterval(3600);
	}

	#[Override]
	protected function run(mixed $argument): void {
		$start = time();
		$workerId = (int)$argument['workerId'];
		$nbWorkers = (int)$argument['nbWorkers'];
		$this->logger->debug('Start to move users from bucket {bucketId} ({nbBuckets} in total).', [
			'bucketId' => $workerId,
			'nbWorkers' => $nbWorkers,
		]);

		// Try to move users until none are moved or time elapsed
		do {
			$this->connection->beginTransaction();
			$nbMoved = $this->moveUsers(0, /*FIXME*/ $workerId, $nbWorkers);
			$this->connection->commit();
		} while ($nbMoved > 0 && time() < ($start + 3300));
	}

	private function moveUsers(int $backendId, int $workerId, int $nbWorkers): int {
		$qb = $this->connection->getQueryBuilder();
		$results = $qb
			->select(
				'uid',
				'displayname',
				'password',
				'disabled.configvalue AS enabled',
				'email.configvalue AS email',
				'lastseen.configvalue AS lastseen',
			)
			->from('users', 'U')
			->leftJoin(
				'U',
				'preferences',
				'email',
				$qb->expr()->andX(
					$qb->expr()->eq('email.userid', 'U.uid'),
					$qb->expr()->eq('email.appid', $qb->expr()->literal('settings')),
					$qb->expr()->eq('email.configkey', $qb->expr()->literal('email')),
				),
			)
			->leftJoin(
				'U',
				'preferences',
				'disabled',
				$qb->expr()->andX(
					$qb->expr()->eq('disabled.userid', 'U.uid'),
					$qb->expr()->eq('disabled.appid', $qb->expr()->literal('core')),
					$qb->expr()->eq('disabled.configkey', $qb->expr()->literal('enabled')),
				),
			)
			->leftJoin(
				'U',
				'preferences',
				'lastseen',
				$qb->expr()->andX(
					$qb->expr()->eq('lastseen.userid', 'U.uid'),
					$qb->expr()->eq('lastseen.appid', $qb->expr()->literal('login')),
					$qb->expr()->eq('lastseen.configkey', $qb->expr()->literal('lastLogin')),
				),
			)
			->setMaxResults(self::BATCH_SIZE)
			->executeQuery();

		if ($results->rowCount() === 0) {
			// User table is empty
			// Remove the job
			$this->jobList->removeById($this->getId());
			return 0;
		}

		$movedIds = [];
		foreach ($results->iterateAssociative() as $user) {
			// Should we handle it?
			$bucketId = hexdec(hash('xxh32', $user['uid'])) % $nbWorkers;
			if ($bucketId !== $workerId) {
				continue;
			}
			try {
				$this->copyUser($backendId, $user);
				$movedIds[] = $user['uid'];
			} catch (Exception $e) {
				$this->logger->warning('Fail to move user {user} to new user table: {error}', [
					'error' => $e->getMessage(),
					'user' => $user['uid'],
					'exception' => $e,
				]);
			}
		}

		if (count($movedIds) === 0) {
			// No matching user to move, but table isn't empty yet.
			// Stop job for now
			return 0;
		}

		// Remove moved users
		$qb
			->delete('users')
			->where($qb->expr()->in('uid', $qb->createNamedParameter($movedIds, IQueryBuilder::PARAM_STR_ARRAY)))
			->executeStatement();

		return count($movedIds);
	}

	private function copyUser(int $backendId, array $user): void {
		$qb = $this->connection->getQueryBuilder();
		$lastSeen = $user['lastseen'] === null ? $qb->createNamedParameter(null) : $qb->func()->fromUnixtime($user['lastseen']);

		$qb
			->insert('user_accounts')
			->setValue('account_id', $qb->createNamedParameter($this->snowflakeGenerator->nextId()))
			->setValue('backend_id', $qb->createNamedParameter($backendId))
			->setValue('account_identifier', $qb->createNamedParameter($user['uid']))
			->setValue('account_primary_email', $qb->createNamedParameter($user['email'] ?? ''))
			->setValue('account_display_name', $qb->createNamedParameter($user['displayname'] ?? $user['uid']))
			->setValue('account_password', $qb->createNamedParameter($user['password']))
			->setValue('legacy_uid', $qb->createNamedParameter($user['uid']))
			->setValue(
				'account_status',
				$qb->createNamedParameter($user['enabled'] === 'false' ? UserStatus::DISABLED->value : UserStatus::ENABLED->value),
			)
			->setValue('account_last_seen', $lastSeen)
			->executeStatement();
	}
}
