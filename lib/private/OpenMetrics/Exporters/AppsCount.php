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
 * Export statistics about apps
 */
class AppsCount implements IMetricFamily {
	public function __construct(
		private IAppManager $appManager,
	) {
	}

	#[Override]
	public function name(): string {
		return 'installed_applications';
	}

	#[Override]
	public function type(): MetricType {
		return MetricType::gauge;
	}

	#[Override]
	public function unit(): string {
		return 'applications';
	}

	#[Override]
	public function help(): string {
		return 'Number of applications installed in Nextcloud';
	}

	#[Override]
	public function metrics(): Generator {
		$installedAppsCount = count($this->appManager->getAppInstalledVersions(false));
		$enabledAppsCount = count($this->appManager->getEnabledApps());
		$disabledAppsCount = $installedAppsCount - $enabledAppsCount;
		yield new Metric(
			$disabledAppsCount,
			['status' => 'disabled'],
		);
		yield new Metric(
			$enabledAppsCount,
			['status' => 'enabled'],
		);
	}
}
