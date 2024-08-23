<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;

/**
 * @since 13.0.0
 */
abstract class BigIntMigration extends SimpleMigrationStep {
	/**
	 * @return array Returns an array with the following structure
	 *               ['table1' => ['column1', 'column2'], ...]
	 * @since 13.0.0
	 */
	abstract protected function getColumnsByTable();

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$tables = $this->getColumnsByTable();

		foreach ($tables as $tableName => $columns) {
			$table = $schema->getTable($tableName);

			foreach ($columns as $columnName) {
				$column = $table->getColumn($columnName);
				if ($column->getType()->getName() !== Types::BIGINT) {
					$column->setType(Type::getType(Types::BIGINT));
					$column->setOptions(['length' => 20]);
				}
			}
		}

		return $schema;
	}
}
