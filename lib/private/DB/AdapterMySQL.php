<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\DB;

class AdapterMySQL extends Adapter {
	/** @var string */
	protected $collation;

	/**
	 * @param string $tableName
	 */
	public function lockTable($tableName) {
		$this->conn->executeUpdate('LOCK TABLES `' .$tableName . '` WRITE');
	}

	public function unlockTable() {
		$this->conn->executeUpdate('UNLOCK TABLES');
	}

	public function fixupStatement($statement) {
		$statement = str_replace(' ILIKE ', ' COLLATE ' . $this->getCollation() . ' LIKE ', $statement);
		return $statement;
	}

	protected function getCollation(): string {
		if (!$this->collation) {
			$params = $this->conn->getParams();
			$this->collation = $params['collation'] ?? (($params['charset'] ?? 'utf8') . '_general_ci');
		}

		return $this->collation;
	}
}
