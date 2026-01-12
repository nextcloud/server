<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OpenMetrics;

use Generator;
use OCP\AppFramework\Attribute\Implementable;

/**
 * @since 33.0.0
 */
#[Implementable(since: '33.0.0')]
interface IMetricFamily {
	/**
	 * Family name (will be prefixed by nextcloud_)
	 *
	 * @since 33.0.0
	 */
	public function name(): string;

	/**
	 * Family metric type
	 *
	 * @since 33.0.0
	 */
	public function type(): MetricType;

	/**
	 * Family unit (can be empty string)
	 * @since 33.0.0
	 */
	public function unit(): string;

	/**
	 * Family help text (can be empty string)
	 * @since 33.0.0
	 */
	public function help(): string;

	/**
	 * List of metrics
	 *
	 * @return Generator<Metric>
	 * @since 33.0.0
	 */
	public function metrics(): Generator;
}
