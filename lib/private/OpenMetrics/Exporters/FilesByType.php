<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OpenMetrics\Exporters;

use Generator;
use OCP\Files\IMimeTypeLoader;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\OpenMetrics\Metric;
use OCP\OpenMetrics\MetricType;
use Override;

/**
 * Export files count
 *
 * Cached exporter, refreshed every 30 minutes
 */
class FilesByType extends Cached {
	public function __construct(
		ICacheFactory $cacheFactory,
		private IDBConnection $connection,
		private IMimeTypeLoader $mimetypeLoader,
	) {
		parent::__construct($cacheFactory);
	}

	#[Override]
	public function name(): string {
		return 'files';
	}

	#[Override]
	public function type(): MetricType {
		return MetricType::gauge;
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
		$qb = $this->connection->getQueryBuilder()->runAcrossAllShards();
		$metrics = $qb->select('mimetype', $qb->func()->count('*', 'count'))
			->from('filecache')
			->where($qb->expr()->like('path', $qb->createNamedParameter('files/%')))
			->groupBy('mimetype')
			->executeQuery();

		if ($metrics->rowCount() === 0) {
			yield new Metric(0);
			return;
		}
		$now = time();
		foreach ($metrics->iterateAssociative() as $count) {
			yield new Metric(
				$count['count'],
				['mimetype' => $this->mimetypeLoader->getMimetypeById($count['mimetype']) ?? ''],
				$now,
			);
		}
	}
}
