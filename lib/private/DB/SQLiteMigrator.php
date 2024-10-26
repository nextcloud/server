<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use Doctrine\DBAL\Schema\Schema;

class SQLiteMigrator extends Migrator {
	/**
	 * @param Schema $targetSchema
	 * @param \Doctrine\DBAL\Connection $connection
	 * @return \Doctrine\DBAL\Schema\SchemaDiff
	 */
	protected function getDiff(Schema $targetSchema, \Doctrine\DBAL\Connection $connection) {
		foreach ($targetSchema->getTables() as $table) {
			foreach ($table->getColumns() as $column) {
				// column comments are not supported on SQLite
				if ($column->getComment() !== null) {
					$column->setComment(null);
				}
			}
		}

		return parent::getDiff($targetSchema, $connection);
	}
}
