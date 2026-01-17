<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OpenMetrics\Exporters;

use Generator;
use OCP\IDBConnection;
use OCP\OpenMetrics\IMetricFamily;
use OCP\OpenMetrics\Metric;
use OCP\OpenMetrics\MetricType;
use Override;

/**
 * Export the number of running jobs by type
 */
class RunningJobs implements IMetricFamily {
	public function __construct(
		private IDBConnection $connection,
	) {
	}

	#[Override]
	public function name(): string {
		return 'jobs_running';
	}

	#[Override]
	public function type(): MetricType {
		return MetricType::gauge;
	}

	#[Override]
	public function unit(): string {
		return 'jobs';
	}

	#[Override]
	public function help(): string {
		return 'Number of running jobs';
	}

	#[Override]
	public function metrics(): Generator {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select($qb->func()->count('*', 'nb'), 'class')
			->from('jobs')
			->where($qb->expr()->gt('reserved_at', $qb->createNamedParameter(0)))
			->groupBy('class')
			->executeQuery();

		// If no result, return a metric with count '0'
		if ($result->rowCount() === 0) {
			yield new Metric(0);
			return;
		}

		foreach ($result->iterateAssociative() as $row) {
			yield new Metric($row['nb'], ['class' => $row['class']]);
		}
	}
}
