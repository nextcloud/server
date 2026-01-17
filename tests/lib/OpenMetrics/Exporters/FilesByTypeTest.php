<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics\Exporters;

use OC\OpenMetrics\Exporters\FilesByType;
use OCP\Files\IMimeTypeLoader;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\OpenMetrics\IMetricFamily;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;

#[Group('DB')]
class FilesByTypeTest extends ExporterTestCase {
	protected function getExporter():IMetricFamily {
		return new FilesByType(
			Server::get(ICacheFactory::class),
			Server::get(IDBConnection::class),
			Server::get(IMimeTypeLoader::class),
		);
	}
}
