<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\DB;

use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Schema;

class OracleMigrator extends NoCheckMigrator {
	/**
	 * @param Schema $targetSchema
	 * @param \Doctrine\DBAL\Connection $connection
	 * @return \Doctrine\DBAL\Schema\SchemaDiff
	 */
	protected function getDiff(Schema $targetSchema, \Doctrine\DBAL\Connection $connection) {
		$schemaDiff = parent::getDiff($targetSchema, $connection);

		// oracle forces us to quote the identifiers
		foreach ($schemaDiff->changedTables as $tableDiff) {
			$tableDiff->name = $this->connection->quoteIdentifier($tableDiff->name);
			foreach ($tableDiff->changedColumns as $column) {
				$column->oldColumnName = $this->connection->quoteIdentifier($column->oldColumnName);
				// auto increment is not relevant for oracle and can anyhow not be applied on change
				$column->changedProperties = array_diff($column->changedProperties, ['autoincrement', 'unsigned']);
			}
			$tableDiff->changedColumns = array_filter($tableDiff->changedColumns, function (ColumnDiff $column) {
				return count($column->changedProperties) > 0;
			});
		}

		return $schemaDiff;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	protected function generateTemporaryTableName($name) {
		return 'oc_' . uniqid();
	}

	/**
	 * @param $statement
	 * @return string
	 */
	protected function convertStatementToScript($statement) {
		if (substr($statement, -1) === ';') {
			return $statement . PHP_EOL . '/' . PHP_EOL;
		}
		$script = $statement . ';';
		$script .= PHP_EOL;
		$script .= PHP_EOL;
		return $script;
	}

	protected function getFilterExpression() {
		return '/^"' . preg_quote($this->config->getSystemValue('dbtableprefix', 'oc_')) . '/';
	}

}
