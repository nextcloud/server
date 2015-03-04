<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;

class SqlitePlatform extends \Doctrine\DBAL\Platforms\SqlitePlatform {
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
