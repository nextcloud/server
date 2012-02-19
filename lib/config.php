<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2010 Frank Karlitschek karlitschek@kde.org
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
 * configuration file of owncloud.
 */
class OC_Config{
	// associative array key => value
	private static $cache = array();

	// Is the cache filled?
	private static $init = false;

	/**
	 * @brief Lists all available config keys
	 * @returns array with key names
	 *
	 * This function returns all keys saved in config.php. Please note that it
	 * does not return the values.
	 */
	public static function getKeys(){
		self::readData();

		return array_keys( self::$cache );
	}

	/**
	 * @brief Gets a value from config.php
	 * @param $key key
	 * @param $default = null default value
	 * @returns the value or $default
	 *
	 * This function gets the value from config.php. If it does not exist,
	 * $default will be returned.
	 */
	public static function getValue( $key, $default = null ){
		self::readData();

		if( array_key_exists( $key, self::$cache )){
			return self::$cache[$key];
		}

		return $default;
	}

	/**
	 * @brief Sets a value
	 * @param $key key
	 * @param $value value
	 * @returns true/false
	 *
	 * This function sets the value and writes the config.php. If the file can
	 * not be written, false will be returned.
	 */
	public static function setValue( $key, $value ){
		self::readData();

		// Add change
		self::$cache[$key] = $value;

		// Write changes
		self::writeData();
		return true;
	}

	/**
	 * @brief Removes a key from the config
	 * @param $key key
	 * @returns true/false
	 *
	 * This function removes a key from the config.php. If owncloud has no
	 * write access to config.php, the function will return false.
	 */
	public static function deleteKey( $key ){
		self::readData();

		if( array_key_exists( $key, self::$cache )){
			// Delete key from cache
			unset( self::$cache[$key] );

			// Write changes
			self::writeData();
		}

		return true;
	}

	/**
	 * @brief Loads the config file
	 * @returns true/false
	 *
	 * Reads the config file and saves it to the cache
	 */
	private static function readData(){
		if( self::$init ){
			return true;
		}

		if( !file_exists( OC::$SERVERROOT."/config/config.php" )){
			return false;
		}

		// Include the file, save the data from $CONFIG
		include( OC::$SERVERROOT."/config/config.php" );
		if( isset( $CONFIG ) && is_array( $CONFIG )){
			self::$cache = $CONFIG;
		}

		// We cached everything
		self::$init = true;

		return true;
	}

	/**
	 * @brief Writes the config file
	 * @returns true/false
	 *
	 * Saves the config to the config file.
	 *
	 * Known flaws: Strings are not escaped properly
	 */
	public static function writeData(){
		// Create a php file ...
		$content = "<?php\n\$CONFIG = array(\n";

		foreach( self::$cache as $key => $value ){
			if( is_bool( $value )){
				$value = $value ? 'true' : 'false';
				$content .= "\"$key\" => $value,\n";
			}
			else{
				$value = str_replace( "'", "\\'", $value );
				$content .= "\"$key\" => '$value',\n";
			}
		}
		$content .= ");\n?>\n";

		// Write the file
		$result=@file_put_contents( OC::$SERVERROOT."/config/config.php", $content );
		if(!$result) {
			$tmpl = new OC_Template( '', 'error', 'guest' );
			$tmpl->assign('errors',array(1=>array('error'=>"Can't write into config directory 'config'",'hint'=>"You can usually fix this by giving the webserver user write access to the config directory in owncloud")));
			$tmpl->printPage();
			exit;
		}
		return true;
	}
}
