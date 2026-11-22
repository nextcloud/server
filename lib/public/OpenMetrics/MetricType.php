<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OpenMetrics;

/**
 * Metrics types
 *
 * @since 33.0.0
 */
enum MetricType {
	/**
	 * @since 33.0.0
	 */
	case counter;
	/**
	 * @since 33.0.0
	 */
	case gauge;
	/**
	 * @since 33.0.0
	 */
	case histogram;
	/**
	 * @since 33.0.0
	 */
	case gaugehistogram;
	/**
	 * @since 33.0.0
	 */
	case stateset;
	/**
	 * @since 33.0.0
	 */
	case info;
	/**
	 * @since 33.0.0
	 */
	case summary;
	/**
	 * @since 33.0.0
	 */
	case unknown;
}
