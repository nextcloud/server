<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OpenMetrics\Exporters;

use Generator;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\OpenMetrics\Metric;
use OCP\OpenMetrics\MetricTypes;
use Override;

/**
 * Export files count
 *
 * Cached exporter, refreshed every 30 minutes
 *
 * @since 33.0.0
 */
class FilesByType extends Cached {
	public function __construct(
		ICacheFactory $cacheFactory,
		private IDBConnection $connection,
	) {
		parent::__construct($cacheFactory);
	}

	#[Override]
	public function name(): string {
		return 'files';
	}

	#[Override]
	public function type(): MetricTypes {
		return MetricTypes::gauge;
	}

	#[Override]
	public function unit(): string {
		return 'files';
	}

	#[Override]
	public function help(): string {
		return 'Number of files by type';
	}

	#[Override]
	public function getTTL(): int {
		return 30 * 60;
	}

	#[Override]
	public function gatherMetrics(): Generator {
		$qb = $this->connection->getQueryBuilder();
		$metrics = $qb->select('M.mimetype', $qb->func()->count('*', 'count'))
			->from('filecache', 'F')
			->join('F', 'mimetypes', 'M', $qb->expr()->eq('F.mimetype', 'M.id'))
			->groupBy('M.mimetype')
			->executeQuery();

		if ($metrics->rowCount() === 0) {
			yield new Metric(0);
			return;
		}
		$now = time();
		while ($count = $metrics->fetch()) {
			yield new Metric($count['count'], ['mimetype' => $count['mimetype']], $now);
		}
	}
}
