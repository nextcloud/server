<?php
/**
 * Copyright (c) 2014 Morris Jobke <hey@morrisjobke.de>
 *               2013 Bart Visscher <bartv@thisnet.nl>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */

namespace OC;

/**
 * Class which provides access to the system config values stored in config.php
 * Internal class for bootstrap only.
 * fixes cyclic DI: AllConfig needs AppConfig needs Database needs AllConfig
 */
class SystemConfig {
	/**
	 * Sets a new system wide value
	 *
	 * @param string $key the key of the value, under which will be saved
	 * @param mixed $value the value that should be stored
	 */
	public function setValue($key, $value) {
		\OC_Config::setValue($key, $value);
	}

	/**
	 * Sets and deletes values and writes the config.php
	 *
	 * @param array $configs Associative array with `key => value` pairs
	 *                       If value is null, the config key will be deleted
	 */
	public function setValues(array $configs) {
		\OC_Config::setValues($configs);
	}

	/**
	 * Looks up a system wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param mixed $default the default value to be returned if the value isn't set
	 * @return mixed the value or $default
	 */
	public function getValue($key, $default = '') {
		return \OC_Config::getValue($key, $default);
	}

	/**
	 * Delete a system wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 */
	public function deleteValue($key) {
		\OC_Config::deleteKey($key);
	}
}
