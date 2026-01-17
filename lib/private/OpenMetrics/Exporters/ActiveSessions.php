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

class ActiveSessions implements IMetricFamily {
	public function __construct(
		private IDBConnection $connection,
	) {
	}

	public function name(): string {
		return 'active_sessions';
	}

	public function type(): MetricType {
		return MetricType::gauge;
	}

	public function unit(): string {
		return 'sessions';
	}

	public function help(): string {
		return 'Number of active sessions';
	}

	public function metrics(): Generator {
		$now = time();
		$timeFrames = [
			'Last 5 minutes' => $now - 5 * 60,
			'Last 15 minutes' => $now - 15 * 60,
			'Last hour' => $now - 60 * 60,
			'Last day' => $now - 24 * 60 * 60,
		];
		foreach ($timeFrames as $label => $time) {
			$queryBuilder = $this->connection->getQueryBuilder();
			$result = $queryBuilder->select($queryBuilder->func()->count('*'))
				->from('authtoken')
				->where($queryBuilder->expr()->gte('last_activity', $queryBuilder->createNamedParameter($time)))
				->executeQuery();

			yield new Metric((int)$result->fetchOne(), ['time' => $label]);
		}
	}
}
