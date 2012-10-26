<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_DB_MDB2SchemaReader {
	static protected $DBNAME;
	static protected $DBTABLEPREFIX;

	public static function loadSchemaFromFile($file) {
		self::$DBNAME  = OC_Config::getValue( "dbname", "owncloud" );
		self::$DBTABLEPREFIX = OC_Config::getValue( "dbtableprefix", "oc_" );
		$schema = new \Doctrine\DBAL\Schema\Schema();
		$xml = simplexml_load_file($file);
		foreach($xml->children() as $child) {
			switch($child->getName()) {
				case 'name':
					$name = (string)$child;
					$name = str_replace( '*dbname*', self::$DBNAME, $name );
					break;
				case 'create':
				case 'overwrite':
				case 'charset':
					break;
				case 'table':
					self::loadTable($schema, $child);
					break;
				default:
					var_dump($child->getName());

			}
		}
		return $schema;
	}

	private static function loadTable($schema, $xml) {
		foreach($xml->children() as $child) {
			switch($child->getName()) {
				case 'name':
					$name = (string)$child;
					$name = str_replace( '*dbprefix*', self::$DBTABLEPREFIX, $name );
					$table = $schema->createTable($name);
					break;
				case 'create':
				case 'overwrite':
				case 'charset':
					break;
				case 'declaration':
					self::loadDeclaration($table, $child);
					break;
				default:
					var_dump($child->getName());

			}
		}
	}

	private static function loadDeclaration($table, $xml) {
		foreach($xml->children() as $child) {
			switch($child->getName()) {
				case 'field':
					self::loadField($table, $child);
					break;
				case 'index':
					self::loadIndex($table, $child);
					break;
				default:
					var_dump($child->getName());

			}
		}
	}

	private static function loadField($table, $xml) {
		$options = array();
		foreach($xml->children() as $child) {
			switch($child->getName()) {
				case 'name':
					$name = (string)$child;
					break;
				case 'type':
					$type = (string)$child;
					switch($type) {
						case 'text':
							$type = 'string';
							break;
						case 'clob':
							$type = 'text';
							break;
						case 'timestamp':
							$type = 'datetime';
							break;
						// TODO
						return;
					}
					break;
				case 'length':
					$length = (string)$child;
					$options['length'] = $length;
					break;
				case 'unsigned':
					$unsigned = self::asBool($child);
					$options['unsigned'] = $unsigned;
					break;
				case 'notnull':
					$notnull = self::asBool($child);
					$options['notnull'] = $notnull;
					break;
				case 'autoincrement':
					$autoincrement = self::asBool($child);
					$options['autoincrement'] = $autoincrement;
					break;
				case 'default':
					$default = (string)$child;
					$options['default'] = $default;
					break;
				default:
					var_dump($child->getName());

			}
		}
		if (isset($name) && isset($type)) {
		if ($name == 'x') {
			var_dump($name, $type, $options);
			echo '<pre>';
			debug_print_backtrace();
		}
			if (empty($options['default'])) {
				if ($type == 'integer') {
					if (empty($options['default'])) {
						if (empty($options['notnull'])) {
							unset($options['default']);
						}
						else {
							$options['default'] = 0;
						}
					}
				}
				if (!empty($options['autoincrement'])) {
					unset($options['default']);
				}
			}
			if ($type == 'integer') {
				$length = $options['length'];
				if ($length == 1) {
					$type = 'boolean';
				}
				else if ($length < 4) {
					$type = 'smallint';
				}
				else if ($length > 4) {
					$type = 'bigint';
				}
			}
			$table->addColumn($name, $type, $options);
			if (!empty($options['autoincrement'])
			    && !empty($options['notnull'])) {
				$table->setPrimaryKey(array($name));
			}
		}
	}

	private static function loadIndex($table, $xml) {
		$name = null;
		$fields = array();
		foreach($xml->children() as $child) {
			switch($child->getName()) {
				case 'name':
					$name = (string)$child;
					break;
				case 'primary':
					$primary = self::asBool($child);
					break;
				case 'unique':
					$unique = self::asBool($child);
					break;
				case 'field':
					foreach($child->children() as $field) {
						switch($field->getName()) {
							case 'name':
								$field_name = (string)$field;
								$fields[] = $field_name;
								break;
							case 'sorting':
								break;
							default:
								var_dump($field->getName());

						}
					}
					break;
				default:
					var_dump($child->getName());

			}
		}
		if (!empty($fields)) {
			if (isset($primary) && $primary) {
				$table->setPrimaryKey($fields, $name);
			} else
			if (isset($unique) && $unique) {
				$table->addUniqueIndex($fields, $name);
			} else {
				$table->addIndex($fields, $name);
			}
		} else {
			var_dump($name, $fields);
		}
	}

	private static function asBool($xml) {
		$result = (string)$xml;
		if ($result == 'true') {
			$result = true;
		} else
		if ($result == 'false') {
			$result = false;
		}
		return (bool)$result;
	}

}
