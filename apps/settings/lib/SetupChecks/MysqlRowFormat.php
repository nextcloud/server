<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use OC\DB\Connection;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class MysqlRowFormat implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private Connection $connection,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('MySQL row format');
	}

	public function getCategory(): string {
		return 'database';
	}

	public function run(): SetupResult {
		if (!$this->connection->getDatabasePlatform() instanceof MySQLPlatform) {
			return SetupResult::success($this->l10n->t('You are not using MySQL'));
		}

		$wrongRowFormatTables = $this->getRowNotDynamicTables();
		if (empty($wrongRowFormatTables)) {
			return SetupResult::success($this->l10n->t('None of your tables use ROW_FORMAT=Compressed'));
		}

		return SetupResult::warning(
			$this->l10n->t(
				'Incorrect row format found in your database. ROW_FORMAT=Dynamic offers the best database performances for Nextcloud. Please update row format on the following list: %s.',
				[implode(', ', $wrongRowFormatTables)],
			),
			'https://dev.mysql.com/doc/refman/en/innodb-row-format.html',
		);
	}

	/**
	 * @return string[]
	 */
	private function getRowNotDynamicTables(): array {
		$sql = "SELECT table_name
			FROM information_schema.tables
			WHERE table_schema = ?
			  AND table_name LIKE '*PREFIX*%'
			  AND row_format != 'Dynamic';";

		return $this->connection->executeQuery(
			$sql,
			[$this->config->getSystemValueString('dbname')],
		)->fetchFirstColumn();
	}
}
