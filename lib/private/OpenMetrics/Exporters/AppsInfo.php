<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OpenMetrics\Exporters;

use Generator;
use OCP\App\IAppManager;
use OCP\OpenMetrics\IMetricFamily;
use OCP\OpenMetrics\Metric;
use OCP\OpenMetrics\MetricType;
use Override;

/**
 * Export information about enabled applications
 */
class AppsInfo implements IMetricFamily {
	public function __construct(
		private IAppManager $appManager,
	) {
	}

	#[Override]
	public function name(): string {
		return 'apps_info';
	}

	#[Override]
	public function type(): MetricType {
		return MetricType::info;
	}

	#[Override]
	public function unit(): string {
		return '';
	}

	#[Override]
	public function help(): string {
		return 'Enabled applications in Nextcloud';
	}

	#[Override]
	public function metrics(): Generator {
		yield new Metric(
			1,
			$this->appManager->getAppInstalledVersions(true),
			time()
		);
	}
}
