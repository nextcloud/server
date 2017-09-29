<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;

class OCPostgreSqlPlatform extends PostgreSqlPlatform {

	/**
	 * {@inheritDoc}
	 */
	public function getAlterTableSQL(TableDiff $diff){
		$queries = parent::getAlterTableSQL($diff);
		foreach ($queries as $index => $sql){
			// BIGSERIAL could not be used in statements altering column type
			// That's why we replace it with BIGINT 
			// see https://github.com/owncloud/core/pull/28364#issuecomment-315006853
			if (preg_match('|(ALTER TABLE\s+\S+\s+ALTER\s+\S+\s+TYPE\s+)(BIGSERIAL)|i', $sql, $matches)) {
				$alterTable = $matches[1];
				$queries[$index] = $alterTable . 'BIGINT';
			}

			// Changing integer to bigint kills next autoincrement value
			// see https://github.com/owncloud/core/pull/28364#issuecomment-315006853
			if (preg_match('|ALTER TABLE\s+(\S+)\s+ALTER\s+(\S+)\s+DROP DEFAULT|i', $sql, $matches)) {
				$queryColumnName = $matches[2];
				$columnDiff = $this->findColumnDiffByName($diff, $queryColumnName);
				if ($columnDiff && $this->shouldSkipDropDefault($columnDiff)) {
					unset($queries[$index]);
					continue;
				}
			}
		}
		
		return $queries;
	}

	/**
	 * We should NOT drop next sequence value if
	 * - type was changed from INTEGER to BIGINT
	 * - column keeps an autoincrement
	 * - default value is kept NULL
	 *
	 * @param ColumnDiff $columnDiff
	 * @return bool
	 */
	private function shouldSkipDropDefault(ColumnDiff $columnDiff) {
		$column = $columnDiff->column;
		$fromColumn = $columnDiff->fromColumn;
		return $fromColumn->getType()->getName() === Type::INTEGER
				&& $column->getType()->getName() === Type::BIGINT
				&& $fromColumn->getDefault() === null
				&& $column->getDefault() === null
				&& $fromColumn->getAutoincrement()
				&& $column->getAutoincrement();
	}

	/**
	 * @param TableDiff $diff
	 * @param string $name
	 * @return  ColumnDiff | false
	 */
	private function findColumnDiffByName(TableDiff $diff, $name) {
		foreach ($diff->changedColumns as $columnDiff) {
			$oldColumnName = $columnDiff->getOldColumnName()->getQuotedName($this);
			if ($oldColumnName === $name) {
				return $columnDiff;
			}
		}
		return false;
	}
}
