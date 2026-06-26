<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\OpenMetrics;

use Generator;
use OCP\IDBConnection;
use OCP\OpenMetrics\IMetricFamily;
use OCP\OpenMetrics\Metric;
use OCP\OpenMetrics\MetricType;
use Override;

class CommentsCountMetric implements IMetricFamily {
	public function __construct(
		private IDBConnection $connection,
	) {
	}

	#[Override]
	public function name(): string {
		return 'comments';
	}

	#[Override]
	public function type(): MetricType {
		return MetricType::gauge;
	}

	#[Override]
	public function unit(): string {
		return 'comments';
	}

	#[Override]
	public function help(): string {
		return 'Number of comments';
	}

	#[Override]
	public function metrics(): Generator {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select($qb->func()->count())
			->from('comments')
			->where($qb->expr()->eq('verb', $qb->expr()->literal('comment')))
			->executeQuery();

		yield new Metric($result->fetchOne(), [], time());
	}
}
