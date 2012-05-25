<?php
/**
* ownCloud
*
* @author Frank Karlitschek
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

/**
 * Public interface of ownCloud for apps to use.
 * Config Class
 *
 */

/**
 * @brief use OCP namespace for all classes that are considered public. 
 *
 * Classes that use this namespace are for use by apps, and not for use by internal
 * OC classes
 */
namespace OCP;

/**
 * This class provides functions to read and write configuration data. configuration can be on a system, application or user level
 */
class Config {


	/**
	 * @brief Gets a value from config.php
	 * @param $key key
	 * @param $default = null default value
	 * @returns the value or $default
	 *
	 * This function gets the value from config.php. If it does not exist,
	 * $default will be returned.
	 */
	public static function getSystemValue( $key, $default = null ){
		return(\OC_Config::getValue( $key, $default ));
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
	public static function setSystemValue( $key, $value ){
		return(\OC_Config::setValue( $key, $value ));
	}


        /**
         * @brief Gets the config value
         * @param $app app
         * @param $key key
         * @param $default = null, default value if the key does not exist
         * @returns the value or $default
         *
         * This function gets a value from the appconfig table. If the key does
         * not exist the default value will be returnes
         */
        public static function getAppValue( $app, $key, $default = null ){
		return(\OC_Appconfig::getValue( $app, $key, $default ));
	}


        /**
         * @brief sets a value in the appconfig
         * @param $app app
         * @param $key key
         * @param $value value
         * @returns true/false
         *
         * Sets a value. If the key did not exist before it will be created.
         */
        public static function setAppValue( $app, $key, $value ){
		return(\OC_Appconfig::setValue( $app, $key, $value ));
	}


        /**
         * @brief Gets the preference
         * @param $user user
         * @param $app app
         * @param $key key
         * @param $default = null, default value if the key does not exist
         * @returns the value or $default
         *
         * This function gets a value from the prefernces table. If the key does
         * not exist the default value will be returnes
         */
        public static function getUserValue( $user, $app, $key, $default = null ){
		return(\OC_Preferences::getValue( $user, $app, $key, $default ));
	}


        /**
         * @brief sets a value in the preferences
         * @param $user user
         * @param $app app
         * @param $key key
         * @param $value value
         * @returns true/false
         *
         * Adds a value to the preferences. If the key did not exist before, it
         * will be added automagically.
         */
        public static function setUserValue( $user, $app, $key, $value ){
		return(\OC_Preferences::setValue(  $user, $app, $key, $value ));
	}






}

?>
