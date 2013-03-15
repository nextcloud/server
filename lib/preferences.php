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
 * The following SQL statement is just a help for developers and will not be
 * executed!
 *
 * CREATE TABLE  `preferences` (
 * `userid` VARCHAR( 255 ) NOT NULL ,
 * `appid` VARCHAR( 255 ) NOT NULL ,
 * `configkey` VARCHAR( 255 ) NOT NULL ,
 * `configvalue` VARCHAR( 255 ) NOT NULL
 * )
 *
 */

/**
 * This class provides an easy way for storing user preferences.
 */
class OC_Preferences{
	/**
	 * @brief Get all users using the preferences
	 * @return array with user ids
	 *
	 * This function returns a list of all users that have at least one entry
	 * in the preferences table.
	 */
	public static function getUsers() {
		// No need for more comments
		$query = OC_DB::prepare( 'SELECT DISTINCT( `userid` ) FROM `*PREFIX*preferences`' );
		$result = $query->execute();

		$users = array();
		while( $row = $result->fetchRow()) {
			$users[] = $row["userid"];
		}

		return $users;
	}

	/**
	 * @brief Get all apps of a user
	 * @param string $user user
	 * @return array with app ids
	 *
	 * This function returns a list of all apps of the user that have at least
	 * one entry in the preferences table.
	 */
	public static function getApps( $user ) {
		// No need for more comments
		$query = OC_DB::prepare( 'SELECT DISTINCT( `appid` ) FROM `*PREFIX*preferences` WHERE `userid` = ?' );
		$result = $query->execute( array( $user ));

		$apps = array();
		while( $row = $result->fetchRow()) {
			$apps[] = $row["appid"];
		}

		return $apps;
	}

	/**
	 * @brief Get the available keys for an app
	 * @param string $user user
	 * @param string $app the app we are looking for
	 * @return array with key names
	 *
	 * This function gets all keys of an app of an user. Please note that the
	 * values are not returned.
	 */
	public static function getKeys( $user, $app ) {
		// No need for more comments
		$query = OC_DB::prepare( 'SELECT `configkey` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ?' );
		$result = $query->execute( array( $user, $app ));

		$keys = array();
		while( $row = $result->fetchRow()) {
			$keys[] = $row["configkey"];
		}

		return $keys;
	}

	/**
	 * @brief Gets the preference
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @param string $default = null, default value if the key does not exist
	 * @return string the value or $default
	 *
	 * This function gets a value from the preferences table. If the key does
	 * not exist the default value will be returned
	 */
	public static function getValue( $user, $app, $key, $default = null ) {
		// Try to fetch the value, return default if not exists.
		$query = OC_DB::prepare( 'SELECT `configvalue` FROM `*PREFIX*preferences`'
			.' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?' );
		$result = $query->execute( array( $user, $app, $key ));

		$row = $result->fetchRow();
		if($row) {
			return $row["configvalue"];
		}else{
			return $default;
		}
	}

	/**
	 * @brief sets a value in the preferences
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @param string $value value
	 * @return bool
	 *
	 * Adds a value to the preferences. If the key did not exist before, it
	 * will be added automagically.
	 */
	public static function setValue( $user, $app, $key, $value ) {
		// Check if the key does exist
		$query = OC_DB::prepare( 'SELECT `configvalue` FROM `*PREFIX*preferences`'
			.' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?' );
		$values=$query->execute(array($user, $app, $key))->fetchAll();
		$exists=(count($values)>0);

		if( !$exists ) {
			$query = OC_DB::prepare( 'INSERT INTO `*PREFIX*preferences`'
				.' ( `userid`, `appid`, `configkey`, `configvalue` ) VALUES( ?, ?, ?, ? )' );
			$query->execute( array( $user, $app, $key, $value ));
		}
		else{
			$query = OC_DB::prepare( 'UPDATE `*PREFIX*preferences` SET `configvalue` = ?'
				.' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?' );
			$query->execute( array( $value, $user, $app, $key ));
		}
		return true;
	}

	/**
	 * @brief Deletes a key
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @return bool
	 *
	 * Deletes a key.
	 */
	public static function deleteKey( $user, $app, $key ) {
		// No need for more comments
		$query = OC_DB::prepare( 'DELETE FROM `*PREFIX*preferences`'
			.' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?' );
		$query->execute( array( $user, $app, $key ));

		return true;
	}

	/**
	 * @brief Remove app of user from preferences
	 * @param string $user user
	 * @param string $app app
	 * @return bool
	 *
	 * Removes all keys in appconfig belonging to the app and the user.
	 */
	public static function deleteApp( $user, $app ) {
		// No need for more comments
		$query = OC_DB::prepare( 'DELETE FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ?' );
		$query->execute( array( $user, $app ));

		return true;
	}

	/**
	 * @brief Remove user from preferences
	 * @param string $user user
	 * @return bool
	 *
	 * Removes all keys in appconfig belonging to the user.
	 */
	public static function deleteUser( $user ) {
		// No need for more comments
		$query = OC_DB::prepare( 'DELETE FROM `*PREFIX*preferences` WHERE `userid` = ?' );
		$query->execute( array( $user ));

		return true;
	}

	/**
	 * @brief Remove app from all users
	 * @param string $app app
	 * @return bool
	 *
	 * Removes all keys in preferences belonging to the app.
	 */
	public static function deleteAppFromAllUsers( $app ) {
		// No need for more comments
		$query = OC_DB::prepare( 'DELETE FROM `*PREFIX*preferences` WHERE `appid` = ?' );
		$query->execute( array( $app ));

		return true;
	}
}
