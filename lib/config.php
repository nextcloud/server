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
 * configuration file of owncloud.
 */
class Config {
	// associative array key => value
	protected $cache = array();

	protected $config_dir;
	protected $config_filename;

	protected $debug_mode;

	public function __construct($config_dir, $debug_mode) {
		$this->config_dir = $config_dir;
		$this->debug_mode = $debug_mode;
		$this->config_filename = $this->config_dir.'config.php';
		$this->readData();
	}
	/**
	 * @brief Lists all available config keys
	 * @return array with key names
	 *
	 * This function returns all keys saved in config.php. Please note that it
	 * does not return the values.
	 */
	public function getKeys() {
		return array_keys( $this->cache );
	}

	/**
	 * @brief Gets a value from config.php
	 * @param string $key key
	 * @param string $default = null default value
	 * @return string the value or $default
	 *
	 * This function gets the value from config.php. If it does not exist,
	 * $default will be returned.
	 */
	public function getValue( $key, $default = null ) {
		if( array_key_exists( $key, $this->cache )) {
			return $this->cache[$key];
		}

		return $default;
	}

	/**
	 * @brief Sets a value
	 * @param string $key key
	 * @param string $value value
	 *
	 * This function sets the value and writes the config.php. If the file can
	 * not be written, false will be returned.
	 */
	public function setValue( $key, $value ) {
		// Add change
		$this->cache[$key] = $value;

		// Write changes
		$this->writeData();
	}

	/**
	 * @brief Removes a key from the config
	 * @param string $key key
	 *
	 * This function removes a key from the config.php. If owncloud has no
	 * write access to config.php, the function will return false.
	 */
	public function deleteKey( $key ) {
		if( array_key_exists( $key, $this->cache )) {
			// Delete key from cache
			unset( $this->cache[$key] );

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
		// read all file in config dir ending by config.php
		$config_files = glob( $this->config_dir.'*.config.php');

		//Filter only regular files
		$config_files = array_filter($config_files, 'is_file');

		//Sort array naturally :
		natsort($config_files);

		// Add default config
		array_unshift($config_files, $this->config_filename);

		//Include file and merge config
		foreach($config_files as $file) {
			if( !file_exists( $file) ) {
				continue;
			}
			unset($CONFIG);
			include $file;
			if( isset( $CONFIG ) && is_array( $CONFIG )) {
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
		if ($this->debug_mode) {
			$content .= "define('DEBUG',true);\n";
		}
		$content .= '$CONFIG = ';
		$content .= var_export($this->cache, true);
		$content .= ";\n";
		//var_dump($content, $this);

		// Write the file
		$result=@file_put_contents( $this->config_filename, $content );
		if(!$result) {
			throw new HintException(
				"Can't write into config directory 'config'",
				'You can usually fix this by giving the webserver user write access'
					.' to the config directory in owncloud');
		}
		// Prevent others not to read the config
		@chmod($this->config_filename, 0640);
	}
}
