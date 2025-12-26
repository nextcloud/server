<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OpenMetrics\Exporters;

use Generator;
use OC\SystemConfig;
use OCP\OpenMetrics\IMetricFamily;
use OCP\OpenMetrics\Metric;
use OCP\OpenMetrics\MetricTypes;
use OCP\Server;
use Override;

/**
 * Export maintenance state
 *
 * @since 33.0.0
 */
class Maintenance implements IMetricFamily {
	public function name(): string {
		return 'maintenance';
	}

	#[Override]
	public function type(): MetricTypes {
		return MetricTypes::info;
	}

	#[Override]
	public function unit(): string {
		return '';
	}

	#[Override]
	public function help(): string {
		return 'Maintenance status of Nextcloud';
	}

	#[Override]
	public function metrics(): Generator {
		$systemConfig = Server::get(SystemConfig::class);
		yield new Metric(
			(bool)$systemConfig->getValue('maintenance', false)
		);
	}
}
