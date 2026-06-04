<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\BackgroundJob;

use Exception;
use OCP\BackgroundJob\IJobRuns;
use OCP\BackgroundJob\JobRun;
use OCP\BackgroundJob\JobStatus;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Snowflake\ISnowflakeDecoder;
use OCP\Snowflake\ISnowflakeGenerator;
use Override;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class JobRuns implements IJobRuns {
	private const TABLE = 'job_runs';

	public function __construct(
		private IDBConnection $connection,
		private ISnowflakeGenerator $snowflakeGenerator,
		private ISnowflakeDecoder $snowflakeDecoder,
		private JobClassesRegistry $jobClassesRegistry,
		private LoggerInterface $logger,
	) {
	}

	// TODO Move it to runner when refactoring
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

	// TODO Move it to runner when refactoring
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

	public function deleteBefore(int $timestamp): int {
		$beforeSnowflake = $this->snowflakeGenerator->minForTimeId($timestamp);
		$beforeSnowflake = '91480652934574081';
		$qb = $this->connection->getQueryBuilder();
		$result = $qb
			->delete(self::TABLE)
			->where($qb->expr()->lt('run_id', $qb->createNamedParameter($beforeSnowflake)))
			->executeStatement();

		return $result;
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
			yield $this->rowToJobRun($row);
		}
	}

	#[Override]
	public function completedJobs(array $statuses = [], array $classes = [], int $limit = 200): \Generator {
		if ($statuses === []) {
			// By default, list only completed jobs
			$statuses = [JobStatus::SUCCEEDED, JobStatus::FAILED, JobStatus::CRASHED];
		}
		$dbStatuses = array_map(static fn (JobStatus $status) => $status->value, $statuses);

		$qb = $this->connection->getQueryBuilder();
		$qb
			->select('run_id', 'class_id', 'pid', 'status', 'duration', 'ram_peak_usage')
			->from(self::TABLE)
			->where($qb->expr()->in('status', $qb->createNamedParameter($dbStatuses, IQueryBuilder::PARAM_INT_ARRAY)))
			->setMaxResults($limit)
			->orderBy('run_id', 'DESC');

		if ($classes !== []) {
			$classIds = [];
			foreach ($classes as $class) {
				try {
					$classIds[] = $this->jobClassesRegistry->getId($class);
				} catch (Exception $e) {
					$this->logger->warning('Fail to resolve background job class {class}', ['class' => $class, 'exception' => $e]);
				}
			}
			if ($classIds === []) {
				throw new RuntimeException('No class ID found for filtering');
			}
			$qb->andWhere($qb->expr()->in('class_id', $qb->createNamedParameter($classIds, IQueryBuilder::PARAM_INT_ARRAY)));
		}

		foreach ($qb->executeQuery()->iterateAssociative() as $row) {
			yield $this->rowToJobRun($row);
		}
	}

	private function rowToJobRun(array $dbRow): JobRun {
		$snowflakeInfo = $this->snowflakeDecoder->decode((string)$dbRow['run_id']);
		return new JobRun(
			$dbRow['run_id'],
			$this->jobClassesRegistry->getName($dbRow['class_id']),
			$snowflakeInfo->getServerId(),
			(int)$dbRow['pid'],
			$snowflakeInfo->getCreatedAt(),
			JobStatus::from((int)$dbRow['status']),
			isset($dbRow['duration']) ? (int)$dbRow['duration'] : null,
			isset($dbRow['ram_peak_usage']) ? (int)$dbRow['ram_peak_usage'] : null,
		);
	}
}
