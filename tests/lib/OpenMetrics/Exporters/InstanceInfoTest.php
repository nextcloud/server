<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics\Exporters;

use OC\OpenMetrics\Exporters\InstanceInfo;
use OC\SystemConfig;
use OCP\OpenMetrics\IMetricFamily;
use OCP\ServerVersion;
use PHPUnit\Framework\MockObject\MockObject;

class InstanceInfoTest extends ExporterTestCase {
	private SystemConfig&MockObject $systemConfig;
	private ServerVersion&MockObject $serverVersion;

	protected function getExporter():IMetricFamily {
		$this->systemConfig = $this->createMock(SystemConfig::class);
		$this->serverVersion = $this->createMock(ServerVersion::class);
		$this->serverVersion->method('getHumanVersion')->willReturn('33.13.17 Gold');
		$this->serverVersion->method('getVersion')->willReturn([33, 13, 17]);
		$this->serverVersion->method('getBuild')->willReturn('dev');

		return new InstanceInfo($this->systemConfig, $this->serverVersion);
	}

	public function testMetrics(): void {
		$this->assertCount(1, $this->metrics);
		$metric = array_pop($this->metrics);
		$this->assertSame([
			'full_version' => '33.13.17 Gold',
			'major_version' => '33',
			'build' => 'dev',
			'installed' => '0',
		], $metric->labels);
	}
}
