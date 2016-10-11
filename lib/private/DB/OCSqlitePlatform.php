<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\DB;

class OCSqlitePlatform extends \Doctrine\DBAL\Platforms\SqlitePlatform {
	/**
	 * {@inheritDoc}
	 */
	public function getColumnDeclarationSQL($name, array $field) {
		$def = parent::getColumnDeclarationSQL($name, $field);
		if (!empty($field['autoincrement'])) {
			$def .= ' PRIMARY KEY AUTOINCREMENT';
		}
		return $def;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function _getCreateTableSQL($name, array $columns, array $options = array()){
		// if auto increment is set the column is already defined as primary key
		foreach ($columns as $column) {
			if (!empty($column['autoincrement'])) {
				$options['primary'] = null;
			}
		}
		return parent::_getCreateTableSQL($name, $columns, $options);
	}
}
