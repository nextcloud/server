<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB\Middleware;

use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;

/**
 * Driver middleware to ensure the session timezone is set to UTC.
 * This ensures consistent timezone handling, regardless of server configuration,
 * similar to how we set the PHP timezone to UTC for Nextcloud.
 *
 * @since 34.0.0
 */
final class UtcTimezoneMiddlewareDriver extends AbstractDriverMiddleware {

	#[\Override]
	public function connect(array $params) {
		$connection = parent::connect($params);
		$platform = $this->getDatabasePlatform();
		if (($platform instanceof MariaDBPlatform) || ($platform instanceof MySQLPlatform)) {
			$connection->exec("SET time_zone = '+00:00'");
		} elseif ($platform instanceof PostgreSQLPlatform) {
			$connection->exec("SET TIME ZONE 'UTC'");
		} elseif ($platform instanceof OraclePlatform) {
			$connection->exec("ALTER SESSION SET TIME_ZONE='Etc/UTC'");
		}
		return $connection;
	}
}
