<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics;

use OC\OpenMetrics\ExporterManager;
use OCP\OpenMetrics\IMetricFamily;
use OCP\Server;
use Test\TestCase;

class ExporterManagerTest extends TestCase {
	public function testExport(): void {
		$exporter = Server::get(ExporterManager::class);
		$this->assertInstanceOf(ExporterManager::class, $exporter);
		foreach ($exporter->export() as $metric) {
			$this->assertInstanceOf(IMetricFamily::class, $metric);
		};
	}
}
