<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\OpenMetrics;

use Generator;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\OpenMetrics\IMetricFamily;
use OCP\OpenMetrics\Metric;
use OCP\OpenMetrics\MetricType;
use OCP\Share\IShare;
use Override;

/**
 * Count shares by type
 * @since 33.0.0
 */
class SharesCountMetric implements IMetricFamily {
	public function __construct(
		private IDBConnection $connection,
	) {
	}

	#[Override]
	public function name(): string {
		return 'shares';
	}

	#[Override]
	public function type(): MetricType {
		return MetricType::gauge;
	}

	#[Override]
	public function unit(): string {
		return 'shares';
	}

	#[Override]
	public function help(): string {
		return 'Number of shares by type';
	}

	#[Override]
	public function metrics(): Generator {
		$types = [
			IShare::TYPE_USER => 'user',
			IShare::TYPE_GROUP => 'group',
			IShare::TYPE_LINK => 'link',
			IShare::TYPE_EMAIL => 'email',
		];
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select($qb->func()->count('*', 'count'), 'share_type')
			->from('share')
			->where($qb->expr()->in('share_type', $qb->createNamedParameter(array_keys($types), IQueryBuilder::PARAM_INT_ARRAY)))
			->groupBy('share_type')
			->executeQuery();

		if ($result->rowCount() === 0) {
			yield new Metric(0);
			return;
		}

		foreach ($result->iterateAssociative() as $row) {
			yield new Metric($row['count'], ['type' => $types[$row['share_type']]]);
		}
	}
}
