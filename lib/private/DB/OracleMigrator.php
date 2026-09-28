<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;

class OracleMigrator extends Migrator {
	/**
	 * @param Schema $targetSchema
	 * @param \Doctrine\DBAL\Connection $connection
	 * @return \Doctrine\DBAL\Schema\SchemaDiff
	 * @throws Exception
	 */
	protected function getDiff(Schema $targetSchema, \Doctrine\DBAL\Connection $connection): \Doctrine\DBAL\Schema\SchemaDiff {
		// oracle forces us to quote the identifiers
		$quotedSchema = new Schema();
		foreach ($targetSchema->getTables() as $table) {
			$quotedTable = $quotedSchema->createTable(
				$this->connection->quoteIdentifier($table->getName()),
			);

			foreach ($table->getColumns() as $column) {
				$newColumn = $quotedTable->addColumn(
					$this->connection->quoteIdentifier($column->getName()),
					$column->getType()->getTypeRegistry()->lookupName($column->getType()),
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
			}

			foreach ($table->getIndexes() as $index) {
				if ($index->isPrimary()) {
					$quotedTable->setPrimaryKey(
						array_map(function ($columnName) {
							return $this->connection->quoteIdentifier($columnName);
						}, $index->getColumns()),
						//TODO migrate existing uppercase indexes, then $this->connection->quoteIdentifier($index->getName()),
						$index->getName(),
					);
				} elseif ($index->isUnique()) {
					$quotedTable->addUniqueIndex(
						array_map(function ($columnName) {
							return $this->connection->quoteIdentifier($columnName);
						}, $index->getColumns()),
						//TODO migrate existing uppercase indexes, then $this->connection->quoteIdentifier($index->getName()),
						$index->getName(),
						$index->getOptions(),
					);
				} else {
					$quotedTable->addIndex(
						array_map(function ($columnName) {
							return $this->connection->quoteIdentifier($columnName);
						}, $index->getColumns()),
						//TODO migrate existing uppercase indexes, then $this->connection->quoteIdentifier($index->getName()),
						$index->getName(),
						$index->getFlags(),
						$index->getOptions(),
					);
				}
			}

			foreach ($table->getUniqueConstraints() as $constraint) {
				$quotedTable->addUniqueConstraint(
					array_map(function ($columnName) {
						return $this->connection->quoteIdentifier($columnName);
					}, $constraint->getColumns()),
					$this->connection->quoteIdentifier($constraint->getName()),
					$constraint->getFlags(),
					$constraint->getOptions(),
				);
			}

			foreach ($table->getForeignKeys() as $foreignKey) {
				$quotedTable->addForeignKeyConstraint(
					$this->connection->quoteIdentifier($foreignKey->getForeignTableName()),
					array_map(function ($columnName) {
						return $this->connection->quoteIdentifier($columnName);
					}, $foreignKey->getLocalColumns()),
					array_map(function ($columnName) {
						return $this->connection->quoteIdentifier($columnName);
					}, $foreignKey->getForeignColumns()),
					$foreignKey->getOptions(),
					$this->connection->quoteIdentifier($foreignKey->getName()),
				);
			}

			foreach ($table->getOptions() as $option => $value) {
				$quotedTable->addOption(
					$option,
					$value,
				);
			}
		}

		foreach ($targetSchema->getSequences() as $sequence) {
			$quotedSchema->createSequence(
				$sequence->getName(),
				$sequence->getAllocationSize(),
				$sequence->getInitialValue(),
			);
		}

		return parent::getDiff($quotedSchema, $connection);
	}

	/**
	 * @param $statement
	 * @return string
	 */
	protected function convertStatementToScript($statement) {
		if (str_ends_with($statement, ';')) {
			return $statement . PHP_EOL . '/' . PHP_EOL;
		}
		$script = $statement . ';';
		$script .= PHP_EOL;
		$script .= PHP_EOL;
		return $script;
	}

	protected function getFilterExpression() {
		return '/^"' . preg_quote($this->config->getSystemValueString('dbtableprefix', 'oc_')) . '/';
	}
}
