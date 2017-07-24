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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

class OracleMigrator extends NoCheckMigrator {
	/**
	 * @param Schema $targetSchema
	 * @param \Doctrine\DBAL\Connection $connection
	 * @return \Doctrine\DBAL\Schema\SchemaDiff
	 * @throws DBALException
	 */
	protected function getDiff(Schema $targetSchema, \Doctrine\DBAL\Connection $connection) {
		$schemaDiff = parent::getDiff($targetSchema, $connection);

		// oracle forces us to quote the identifiers
		$schemaDiff->newTables = array_map(function (Table $table) {
			return new Table(
				$this->connection->quoteIdentifier($table->getName()),
				array_map(function (Column $column) {
					$newColumn = new Column(
						$this->connection->quoteIdentifier($column->getName()),
						$column->getType()
					);
					$newColumn->setAutoincrement($column->getAutoincrement());
					$newColumn->setColumnDefinition($column->getColumnDefinition());
					$newColumn->setComment($column->getComment());
					$newColumn->setDefault($column->getDefault());
					$newColumn->setFixed($column->getFixed());
					$newColumn->setLength($column->getLength());
					$newColumn->setNotnull($column->getNotnull());
					$newColumn->setPrecision($column->getPrecision());
					$newColumn->setScale($column->getScale());
					$newColumn->setUnsigned($column->getUnsigned());
					$newColumn->setPlatformOptions($column->getPlatformOptions());
					$newColumn->setCustomSchemaOptions($column->getPlatformOptions());
					return $newColumn;
				}, $table->getColumns()),
				array_map(function (Index $index) {
					return new Index(
						$this->connection->quoteIdentifier($index->getName()),
						array_map(function ($columnName) {
							return $this->connection->quoteIdentifier($columnName);
						}, $index->getColumns()),
						$index->isUnique(),
						$index->isPrimary(),
						$index->getFlags(),
						$index->getOptions()
					);
				}, $table->getIndexes()),
				$table->getForeignKeys(),
				0,
				$table->getOptions()
			);
		}, $schemaDiff->newTables);

		$schemaDiff->removedTables = array_map(function (Table $table) {
			return new Table(
				$this->connection->quoteIdentifier($table->getName()),
				$table->getColumns(),
				$table->getIndexes(),
				$table->getForeignKeys(),
				0,
				$table->getOptions()
			);
		}, $schemaDiff->removedTables);

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
