<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics\Exporters;

use OC\OpenMetrics\Exporters\AppsCount;
use OCP\App\IAppManager;
use OCP\OpenMetrics\IMetricFamily;

class AppsCountTest extends ExporterTestCase {
	private IAppManager $appManager;

	protected function getExporter():IMetricFamily {
		$this->appManager = $this->createMock(IAppManager::class);
		$this->appManager->method('getAppInstalledVersions')
			->with(false)
			->willReturn(['app1', 'app2', 'app3',  'app4',  'app5']);
		$this->appManager->method('getEnabledApps')
			->willReturn(['app1', 'app2', 'app3']);
		return new AppsCount($this->appManager);
	}

	public function testMetrics(): void {
		foreach ($this->metrics as $metric) {
			$expectedValue = match ($metric->label('status')) {
				'disabled' => 2,
				'enabled' => 3,
			};
			$this->assertEquals($expectedValue, $metric->value);
		}
	}
}
