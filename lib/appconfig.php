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
 * The following SQL statement is just a help for developers and will not be
 * executed!
 *
 * CREATE TABLE  `appconfig` (
 * `appid` VARCHAR( 255 ) NOT NULL ,
 * `key` VARCHAR( 255 ) NOT NULL ,
 * `value` VARCHAR( 255 ) NOT NULL
 * )
 *
 */

/**
 * This class provides an easy way for apps to store config values in the
 * database.
 */
class OC_APPCONFIG{
	/**
	 * @brief Get all apps using the config
	 * @returns array with app ids
	 *
	 * This function returns a list of all apps that have at least one
	 * entry in the appconfig table.
	 */
	public static function getApps(){
		// TODO: write function
		return array();
	}

	/**
	 * @brief Get the available keys for an app
	 * @param $app the app we are looking for
	 * @returns array with key names
	 *
	 * This function gets all keys of an app. Please note that the values are
	 * not returned.
	 */
	public static function getKeys( $app ){
		// TODO: write function
		return array();
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
	public static function getValue( $app, $key, $default = null ){
		// OC_DB::query( $query);
		return $default;
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
	public static function setValue( $app, $key, $value ){
		// TODO: write function
		return true;
	}

	/**
	 * @brief Deletes a key
	 * @param $app app
	 * @param $key key
	 * @returns true/false
	 *
	 * Deletes a key.
	 */
	public static function deleteKey( $app, $key ){
		// TODO: write function
		return true;
	}

	/**
	 * @brief Remove app from appconfig
	 * @param $app app
	 * @returns true/false
	 *
	 * Removes all keys in appconfig belonging to the app.
	 */
	public static function deleteApp( $app ){
		// TODO: write function
		return true;
	}
}
?>
