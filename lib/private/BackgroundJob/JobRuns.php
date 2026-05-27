<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\BackgroundJob;

use OCP\BackgroundJob\IJobRuns;
use OCP\BackgroundJob\JobRun;
use OCP\BackgroundJob\JobStatus;
use OCP\IDBConnection;
use OCP\Snowflake\ISnowflakeDecoder;
use OCP\Snowflake\ISnowflakeGenerator;
use Override;

final readonly class JobRuns implements IJobRuns {
	private const TABLE = 'job_runs';

	public function __construct(
		private IDBConnection $connection,
		private ISnowflakeGenerator $snowflakeGenerator,
		private ISnowflakeDecoder $snowflakeDecoder,
		private JobClassesRegistry $classesRegistry,
	) {
	}

	#[Override]
	public function started(int|string $classId): string {
		$id = $this->snowflakeGenerator->nextId();
		$qb = $this->connection->getQueryBuilder();
		$qb
			->insert(self::TABLE)
			->setValue('run_id', $id)
			->setValue('class_id', $qb->createNamedParameter($classId))
			->setValue('pid', $qb->createNamedParameter(posix_getpid()))
			->setValue('status', $qb->createNamedParameter(JobStatus::RUNNING->value))
			->executeStatement();

		return $id;
	}

	#[Override]
	public function finished(int|string $runId, int $duration, int $memoryPeakUsage, JobStatus $status = JobStatus::SUCCEEDED): bool {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb
			->update(self::TABLE)
			->set('status', $qb->createNamedParameter($status->value))
			->set('duration', $qb->createNamedParameter($duration))
			->set('ram_peak_usage', $qb->createNamedParameter($memoryPeakUsage))
			->where($qb->expr()->eq('run_id', $qb->createNamedParameter($runId)))
			->executeStatement();

		return $result === 1;
	}

	#[Override]
	public function runningJobs(int $limit = 200): \Generator {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb
			->select('run_id', 'class_id', 'pid', 'status')
			->from(self::TABLE)
			->where($qb->expr()->eq('status', $qb->createNamedParameter(JobStatus::RUNNING->value)))
			->setMaxResults($limit)
			->executeQuery();

		foreach ($result->iterateAssociative() as $row) {
			$snowflakeInfo = $this->snowflakeDecoder->decode((string)$row['run_id']);
			yield new JobRun(
				$row['run_id'],
				$this->classesRegistry->getName($row['class_id']),
				$snowflakeInfo->getServerId(),
				(int)$row['pid'],
				$snowflakeInfo->getCreatedAt(),
				JobStatus::from((int)$row['status']),
			);
		}
	}
}
