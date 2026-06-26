<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics\Exporters;

use OC\OpenMetrics\Exporters\AppEnabled;
use OCP\App\IAppManager;
use OCP\OpenMetrics\IMetricFamily;
use OCP\OpenMetrics\Metric;

class AppEnabledTest extends ExporterTestCase {
	private IAppManager $appManager;
	private array $appList = [
		'appA' => '0.1.2',
		'appB' => '1.2.3 beta 4',
		'appC' => '6.2.1',
		'appD' => '3.141.5',
	];
	private array $installedAppsList = [
		'appA', 'appB', 'appD'
	];

	#[\Override]
	protected function getExporter():IMetricFamily {
		$this->appManager = $this->createMock(IAppManager::class);
		$this->appManager->method('getAppInstalledVersions')
			->with(false)
			->willReturn($this->appList);
		$this->appManager->method('getEnabledApps')
			->willReturn($this->installedAppsList);

		return new AppEnabled($this->appManager);
	}

	public function testMetrics(): void {
		$this->assertCount(4, $this->metrics);
		foreach ($this->appList as $appId => $appVersion) {
			$metricForApp = array_find($this->metrics, function (Metric $metric) use ($appId) {
				return $metric->label('app_id') === $appId;
			});

			$expectedMetricValue = in_array($appId, $this->installedAppsList) ? 1 : 0;
			$this->assertEquals($expectedMetricValue, $metricForApp->value);
			$this->assertSame(['app_id' => $appId, 'version' => $appVersion], $metricForApp->labels);
		}
	}
}
