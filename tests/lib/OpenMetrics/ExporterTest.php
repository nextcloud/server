<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics;

use OC\OpenMetrics\Exporter;
use OCP\OpenMetrics\IMetricFamily;
use OCP\Server;
use Test\TestCase;

class ExporterTest extends TestCase {
	public function testExport(): void {
		$exporter = Server::get(Exporter::class);
		$this->assertInstanceOf(Exporter::class, $exporter);
		foreach ($exporter() as $metric) {
			$this->assertInstanceOf(IMetricFamily::class, $metric);
		};
	}
}
