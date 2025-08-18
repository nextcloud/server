<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics\Exporters;

use OC\OpenMetrics\Exporters\ActiveSessions;
use OCP\IDBConnection;
use OCP\OpenMetrics\IMetricFamily;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;

#[Group('DB')]
class ActiveSessionsTest extends ExporterTestCase {
	protected function getExporter():IMetricFamily {
		return new ActiveSessions(Server::get(IDBConnection::class));
	}

	public function testMetricsLabel(): void {
		$this->assertLabelsAre([
			['time' => 'Last 5 minutes'],
			['time' => 'Last 15 minutes'],
			['time' => 'Last hour'],
			['time' => 'Last day'],
		]);
	}
}
