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

class ActiveUsers implements IMetricFamily {
	public function __construct(
		private IDBConnection $connection,
	) {
	}

	public function name(): string {
		return 'active_users';
	}

	public function type(): MetricType {
		return MetricType::gauge;
	}

	public function unit(): string {
		return 'users';
	}

	public function help(): string {
		return 'Number of active users';
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
			$qb = $this->connection->getQueryBuilder();
			$result = $qb->select($qb->createFunction('COUNT(DISTINCT ' . $qb->getColumnName('uid') . ')'))
				->from('authtoken')
				->where($qb->expr()->gte('last_activity', $qb->createNamedParameter($time)))
				->executeQuery();

			yield new Metric((int)$result->fetchOne(), ['time' => $label]);
		}
	}
}
