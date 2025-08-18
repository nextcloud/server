<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OpenMetrics\Exporters;

use Generator;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\OpenMetrics\IMetricFamily;
use Override;

/**
 * Cached metrics
 */
abstract class Cached implements IMetricFamily {
	private readonly ICache $cache;

	public function __construct(
		ICacheFactory $cacheFactory,
	) {
		$this->cache = $cacheFactory->createDistributed('openmetrics');
	}

	/**
	 * Number of seconds to keep the results
	 */
	abstract public function getTTL(): int;

	/**
	 * Actually gather the metrics
	 *
	 * @see metrics
	 */
	abstract public function gatherMetrics(): Generator;

	#[Override]
	public function metrics(): Generator {
		$cacheKey = static::class;
		if ($data = $this->cache->get($cacheKey)) {
			yield from unserialize($data);
			return;
		}

		$data = [];
		foreach ($this->gatherMetrics() as $metric) {
			yield $metric;
			$data[] = $metric;
		}

		$this->cache->set($cacheKey, serialize($data), $this->getTTL());
	}
}
