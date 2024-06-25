<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
			$statement = $this->connection->prepare("SHOW VARIABLES LIKE 'version';");
			$result = $statement->execute();
			$row = $result->fetch();
			$version = $row['Value'];
			$versionlc = strtolower($version);
			// we only care about X.Y not X.Y.Z differences
			[$major, $minor, ] = explode('.', $versionlc);
			$versionConcern = $major . '.' . $minor;
			if (str_contains($versionlc, 'mariadb')) {
				if (version_compare($versionConcern, '10.3', '=')) {
					return SetupResult::info($this->l10n->t('MariaDB version 10.3 detected, this version is end-of-life and only supported as part of Ubuntu 20.04. MariaDB >=10.6 and <=11.4 is suggested for best performance, stability and functionality with this version of Nextcloud.'));
				} elseif (version_compare($versionConcern, '10.6', '<') || version_compare($versionConcern, '11.4', '>')) {
					return SetupResult::warning($this->l10n->t('MariaDB version "%s" detected. MariaDB >=10.6 and <=11.4 is suggested for best performance, stability and functionality with this version of Nextcloud.', $version));
				}
			} else {
				if (version_compare($versionConcern, '8.0', '<') || version_compare($versionConcern, '8.4', '>')) {
					return SetupResult::warning($this->l10n->t('MySQL version "%s" detected. MySQL >=8.0 and <=8.4 is suggested for best performance, stability and functionality with this version of Nextcloud.', $version));
				}
			}
		} elseif ($databasePlatform instanceof PostgreSQLPlatform) {
			$statement = $this->connection->prepare('SHOW server_version;');
			$result = $statement->execute();
			$row = $result->fetch();
			$version = $row['server_version'];
			$versionlc = strtolower($version);
			// we only care about X not X.Y or X.Y.Z differences
			[$major, ] = explode('.', $versionlc);
			$versionConcern = $major;
			if (version_compare($versionConcern, '12', '<') || version_compare($versionConcern, '16', '>')) {
				return SetupResult::warning($this->l10n->t('PostgreSQL version "%s" detected. PostgreSQL >=12 and <=16 is suggested for best performance, stability and functionality with this version of Nextcloud.', $version));
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
