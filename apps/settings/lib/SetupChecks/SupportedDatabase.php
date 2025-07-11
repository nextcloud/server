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

	private const MIN_MARIADB = '10.6';
	private const MAX_MARIADB = '11.4';
	private const MIN_MYSQL = '8.0';
	private const MAX_MYSQL = '8.4';
	private const MIN_POSTGRES = '13';
	private const MAX_POSTGRES = '17';

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
					return SetupResult::info(
						$this->l10n->t(
							'MariaDB version 10.3 detected, this version is end-of-life and only supported as part of Ubuntu 20.04. MariaDB >=%1$s and <=%2$s is suggested for best performance, stability and functionality with this version of Nextcloud.',
							[
								self::MIN_MARIADB,
								self::MAX_MARIADB,
							]
						),
					);
				} elseif (version_compare($versionConcern, self::MIN_MARIADB, '<') || version_compare($versionConcern, self::MAX_MARIADB, '>')) {
					return SetupResult::warning(
						$this->l10n->t(
							'MariaDB version "%1$s" detected. MariaDB >=%2$s and <=%3$s is suggested for best performance, stability and functionality with this version of Nextcloud.',
							[
								$version,
								self::MIN_MARIADB,
								self::MAX_MARIADB,
							],
						),
					);
				}
			} else {
				if (version_compare($versionConcern, self::MIN_MYSQL, '<') || version_compare($versionConcern, self::MAX_MYSQL, '>')) {
					return SetupResult::warning(
						$this->l10n->t(
							'MySQL version "%1$s" detected. MySQL >=%2$s and <=%3$s is suggested for best performance, stability and functionality with this version of Nextcloud.',
							[
								$version,
								self::MIN_MYSQL,
								self::MAX_MYSQL,
							],
						),
					);
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
			if (version_compare($versionConcern, self::MIN_POSTGRES, '<') || version_compare($versionConcern, self::MAX_POSTGRES, '>')) {
				return SetupResult::warning(
					$this->l10n->t(
						'PostgreSQL version "%1$s" detected. PostgreSQL >=%2$s and <=%3$s is suggested for best performance, stability and functionality with this version of Nextcloud.',
						[
							$version,
							self::MIN_POSTGRES,
							self::MAX_POSTGRES,
						])
				);
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
