<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\OpenMetrics;

use Generator;
use OC\DB\Connection;
use OCP\OpenMetrics\IMetricFamily;
use OCP\OpenMetrics\Metric;
use OCP\OpenMetrics\MetricTypes;
use Override;

class Comments implements IMetricFamily {
	public function __construct(
		private Connection $connection,
	) {
	}

	#[Override]
	public function name(): string {
		return 'comments';
	}

	#[Override]
	public function type(): MetricTypes {
		return MetricTypes::gauge;
	}

	#[Override]
	public function unit(): string {
		return 'comments';
	}

	#[Override]
	public function help(): string {
		return 'Comments counts';
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
