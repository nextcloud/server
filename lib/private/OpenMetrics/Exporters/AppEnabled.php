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
 * Export information about installed applications
 */
class AppEnabled implements IMetricFamily {
	public function __construct(
		private IAppManager $appManager,
	) {
	}

	#[Override]
	public function name(): string {
		return 'app_enabled';
	}

	#[Override]
	public function type(): MetricType {
		return MetricType::gauge;
	}

	#[Override]
	public function unit(): string {
		return '';
	}

	#[Override]
	public function help(): string {
		return 'Information about the installed Nextcloud applications';
	}

	#[Override]
	public function metrics(): Generator {
		$apps = [];
		$enabledApps = $this->appManager->getEnabledApps();
		foreach ($this->appManager->getAppInstalledVersions(false) as $appId => $version) {
			yield new Metric(in_array($appId, $enabledApps, true) ? 1 : 0, ['app_id' => $appId, 'version' => $version]);
		}
	}
}
