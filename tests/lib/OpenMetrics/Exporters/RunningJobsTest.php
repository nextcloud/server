<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics\Exporters;

use OC\OpenMetrics\Exporters\RunningJobs;
use OCP\IDBConnection;
use OCP\OpenMetrics\IMetricFamily;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;

#[Group('DB')]
class RunningJobsTest extends ExporterTestCase {
	protected function getExporter():IMetricFamily {
		return new RunningJobs(Server::get(IDBConnection::class));
	}
}
