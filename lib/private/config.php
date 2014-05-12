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

namespace OC;

/**
 * This class is responsible for reading and writing config.php, the very basic
 * configuration file of ownCloud.
 */
class Config {
	// associative array key => value
	protected $cache = array();

	protected $configDir;
	protected $configFilename;

	protected $debugMode;

	/**
	 * @param string $configDir path to the config dir, needs to end with '/'
	 */
	public function __construct($configDir) {
		$this->configDir = $configDir;
		$this->configFilename = $this->configDir.'config.php';
		$this->readData();
		$this->setDebugMode(defined('DEBUG') && DEBUG);
	}

	public function setDebugMode($enable) {
		$this->debugMode = $enable;
	}

	/**
	 * @brief Lists all available config keys
	 * @return array an array of key names
	 *
	 * This function returns all keys saved in config.php. Please note that it
	 * does not return the values.
	 */
	public function getKeys() {
		return array_keys($this->cache);
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
	public function getValue($key, $default = null) {
		if (isset($this->cache[$key])) {
			return $this->cache[$key];
		}

		return $default;
	}

	/**
	 * @brief Sets a value
	 * @param string $key key
	 * @param mixed $value value
	 *
	 * This function sets the value and writes the config.php.
	 *
	 */
	public function setValue($key, $value) {
		// Add change
		$this->cache[$key] = $value;

		// Write changes
		$this->writeData();
	}

	/**
	 * @brief Removes a key from the config
	 * @param string $key key
	 *
	 * This function removes a key from the config.php.
	 *
	 */
	public function deleteKey($key) {
		if (isset($this->cache[$key])) {
			// Delete key from cache
			unset($this->cache[$key]);

			// Write changes
			$this->writeData();
		}
	}

	/**
	 * @brief Loads the config file
	 *
	 * Reads the config file and saves it to the cache
	 */
	private function readData() {
		// Default config
		$configFiles = array($this->configFilename);
		// Add all files in the config dir ending with config.php
		$extra = glob($this->configDir.'*.config.php');
		if (is_array($extra)) {
			natsort($extra);
			$configFiles = array_merge($configFiles, $extra);
		}
		// Include file and merge config
		foreach ($configFiles as $file) {
			if (!file_exists($file)) {
				continue;
			}
			unset($CONFIG);
			// ignore errors on include, this can happen when doing a fresh install
			@include $file;
			if (isset($CONFIG) && is_array($CONFIG)) {
				$this->cache = array_merge($this->cache, $CONFIG);
			}
		}
	}

	/**
	 * @brief Writes the config file
	 *
	 * Saves the config to the config file.
	 *
	 */
	private function writeData() {
		// Create a php file ...
		$content = "<?php\n";
		if ($this->debugMode) {
			$content .= "define('DEBUG',true);\n";
		}
		$content .= '$CONFIG = ';
		$content .= var_export($this->cache, true);
		$content .= ";\n";

		// Write the file
		$result = @file_put_contents($this->configFilename, $content);
		if (!$result) {
			$defaults = new \OC_Defaults;
			$url = \OC_Helper::linkToDocs('admin-dir_permissions');
			throw new HintException(
				"Can't write into config directory!",
				'This can usually be fixed by '
					.'<a href="' . $url . '" target="_blank">giving the webserver write access to the config directory</a>.');
		}
		// Prevent others not to read the config
		@chmod($this->configFilename, 0640);
		\OC_Util::clearOpcodeCache();
	}
}

