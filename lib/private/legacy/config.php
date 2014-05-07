<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
/*
 *
 * An example of config.php
 *
 * <?php
 * $CONFIG = array(
 *     "database" => "mysql",
 *     "firstrun" => false,
 *     "pi" => 3.14
 * );
 * ?>
 *
 */

/**
 * This class is responsible for reading and writing config.php, the very basic
 * configuration file of ownCloud.
 */
class OC_Config {

	/**
	 * @var \OC\Config
	 */
	public static $object;

	public static function getObject() {
		return self::$object;
	}

	/**
	 * @brief Lists all available config keys
	 * @return array with key names
	 *
	 * This function returns all keys saved in config.php. Please note that it
	 * does not return the values.
	 */
	public static function getKeys() {
		return self::$object->getKeys();
	}

	/**
	 * @brief Gets a value from config.php
	 * @param string $key key
	 * @param mixed $default = null default value
	 * @return mixed the value or $default
	 *
	 * This function gets the value from config.php. If it does not exist,
	 * $default will be returned.
	 */
	public static function getValue($key, $default = null) {
		return self::$object->getValue($key, $default);
	}

	/**
	 * @brief Sets a value
	 * @param string $key key
	 * @param mixed $value value
	 *
	 * This function sets the value and writes the config.php.
	 *
	 */
	public static function setValue($key, $value) {
		self::$object->setValue($key, $value);
	}

	/**
	 * @brief Removes a key from the config
	 * @param string $key key
	 *
	 * This function removes a key from the config.php.
	 *
	 */
	public static function deleteKey($key) {
		self::$object->deleteKey($key);
	}
}
