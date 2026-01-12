<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics\Exporters;

use OC\OpenMetrics\Exporters\AppsInfo;
use OCP\App\IAppManager;
use OCP\OpenMetrics\IMetricFamily;

class AppsInfoTest extends ExporterTestCase {
	private IAppManager $appManager;
	private array $appList = [
		'appA' => '0.1.2',
		'appB' => '1.2.3 beta 4',
	];

	protected function getExporter():IMetricFamily {
		$this->appManager = $this->createMock(IAppManager::class);
		$this->appManager->method('getAppInstalledVersions')
			->with(true)
			->willReturn($this->appList);

		return new AppsInfo($this->appManager);
	}

	public function testMetrics(): void {
		$this->assertCount(1, $this->metrics);
		$metric = array_pop($this->metrics);
		$this->assertSame($this->appList, $metric->labels);
	}
}
