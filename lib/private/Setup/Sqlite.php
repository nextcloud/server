<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Setup;

use OC\DB\ConnectionFactory;

class Sqlite extends AbstractDatabase {
	public $dbprettyname = 'Sqlite';

	public function validate($config) {
		return [];
	}

	public function initialize($config) {
		/*
		 * Web: When using web based installer its not possible to set dbname
		 * or dbtableprefix. Defaults used from ConnectionFactory and dbtype = 'sqlite'
		 * is written to config.php.
		 *
		 * Cli: When --database-name or --database-table-prefix empty or default
		 * dbtype = 'sqlite' is written to config.php. If you choose a value different
		 * from default these values are written to config.php. This is required because
		 * in connection factory configuration is obtained from config.php.
		 */

		$this->dbName = empty($config['dbname'])
			? ConnectionFactory::DEFAULT_DBNAME
			: $config['dbname'];

		$this->tablePrefix = empty($config['dbtableprefix'])
			? ConnectionFactory::DEFAULT_DBTABLEPREFIX
			: $config['dbtableprefix'];

		if ($this->dbName !== ConnectionFactory::DEFAULT_DBNAME) {
			$this->config->setValue('dbname', $this->dbName);
		}

		if ($this->tablePrefix !== ConnectionFactory::DEFAULT_DBTABLEPREFIX) {
			$this->config->setValue('dbtableprefix', $this->tablePrefix);
		}
	}

	public function setupDatabase() {
		$datadir = $this->config->getValue(
			'datadirectory',
			\OC::$SERVERROOT . '/data'
		);

		$sqliteFile = $datadir . '/' . $this->dbName . 'db';
		if (file_exists($sqliteFile)) {
			unlink($sqliteFile);
		}
	}
}
