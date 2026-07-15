<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OpenMetrics;

/**
 * Special values for metrics
 * @since 33.0.0
 */
enum MetricValue: string {
	/**
	 * @since 33.0.0
	 */
	case NOT_A_NUMBER = 'NaN';
	/**
	 * @since 33.0.0
	 */
	case POSITIVE_INFINITY = '+Inf';
	/**
	 * @since 33.0.0
	 */
	case NEGATIVE_INFINITY = '-Inf';
}
