<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics\Exporters;

use OC\OpenMetrics\Exporters\Maintenance;
use OCP\OpenMetrics\IMetricFamily;

class MaintenanceTest extends ExporterTestCase {
	protected function getExporter():IMetricFamily {
		return new Maintenance();
	}
}
