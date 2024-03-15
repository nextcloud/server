<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Morris Jobke <hey@morrisjobke.de>
 *
 * @author Claas Augner <github@caugner.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\SetupChecks;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class SupportedDatabase implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IDBConnection $connection,
	) {
	}

	public function getCategory(): string {
		return 'database';
	}

	public function getName(): string {
		return $this->l10n->t('Database version');
	}

	public function run(): SetupResult {
		$version = null;
		$databasePlatform = $this->connection->getDatabasePlatform();
		if ($databasePlatform instanceof MySQLPlatform) {
			$result = $this->connection->prepare("SHOW VARIABLES LIKE 'version';");
			$result->execute();
			$row = $result->fetch();
			$version = $row['Value'];
			$versionlc = strtolower($version);

			if (str_contains($versionlc, 'mariadb')) {
				if (version_compare($versionlc, '10.2', '<')) {
					return SetupResult::warning($this->l10n->t('MariaDB version "%s" is used. Nextcloud 21 and higher do not support this version and require MariaDB 10.2 or higher.', $version));
				}
			} else {
				if (version_compare($versionlc, '8', '<')) {
					return SetupResult::warning($this->l10n->t('MySQL version "%s" is used. Nextcloud 21 and higher do not support this version and require MySQL 8.0 or MariaDB 10.2 or higher.', $version));
				}
			}
		} elseif ($databasePlatform instanceof PostgreSQLPlatform) {
			$result = $this->connection->prepare('SHOW server_version;');
			$result->execute();
			$row = $result->fetch();
			$version = $row['server_version'];
			if (version_compare(strtolower($version), '9.6', '<')) {
				return SetupResult::warning($this->l10n->t('PostgreSQL version "%s" is used. Nextcloud 21 and higher do not support this version and require PostgreSQL 9.6 or higher.', $version));
			}
		} elseif ($databasePlatform instanceof OraclePlatform) {
			$version = 'Oracle';
		} elseif ($databasePlatform instanceof SqlitePlatform) {
			return SetupResult::warning(
				$this->l10n->t('SQLite is currently being used as the backend database. For larger installations we recommend that you switch to a different database backend. This is particularly recommended when using the desktop client for file synchronisation. To migrate to another database use the command line tool: "occ db:convert-type".'),
				$this->urlGenerator->linkToDocs('admin-db-conversion')
			);
		} else {
			return SetupResult::error($this->l10n->t('Unknown database platform'));
		}
		return SetupResult::success($version);
	}
}
