<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics\Exporters;

use OC\OpenMetrics\Exporters\LogLevel;
use OCP\IConfig;
use OCP\OpenMetrics\IMetricFamily;
use OCP\Server;

class LogLevelTest extends ExporterTestCase {
	protected function getExporter():IMetricFamily {
		return new LogLevel(Server::get(IConfig::class));
	}
}
