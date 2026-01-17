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
	case counter;
	case gauge;
	case histogram;
	case gaugehistogram;
	case stateset;
	case info;
	case summary;
	case unknown;
}
