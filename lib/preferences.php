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
	 * @returns array with user ids
	 *
	 * This function returns a list of all users that have at least one entry
	 * in the preferences table.
	 */
	public static function getUsers(){
		// No need for more comments
		$query = OC_DB::prepare( 'SELECT DISTINCT( userid ) FROM *PREFIX*preferences' );
		$result = $query->execute();

		$users = array();
		while( $row = $result->fetchRow()){
			$users[] = $row["userid"];
		}

		return $users;
	}

	/**
	 * @brief Get all apps of a user
	 * @param $user user
	 * @returns array with app ids
	 *
	 * This function returns a list of all apps of the user that have at least
	 * one entry in the preferences table.
	 */
	public static function getApps( $user ){
		// No need for more comments
		$query = OC_DB::prepare( 'SELECT DISTINCT( appid ) FROM *PREFIX*preferences WHERE userid = ?' );
		$result = $query->execute( array( $user ));

		$apps = array();
		while( $row = $result->fetchRow()){
			$apps[] = $row["appid"];
		}

		return $apps;
	}

	/**
	 * @brief Get the available keys for an app
	 * @param $user user
	 * @param $app the app we are looking for
	 * @returns array with key names
	 *
	 * This function gets all keys of an app of an user. Please note that the
	 * values are not returned.
	 */
	public static function getKeys( $user, $app ){
		// No need for more comments
		$query = OC_DB::prepare( 'SELECT configkey FROM *PREFIX*preferences WHERE userid = ? AND appid = ?' );
		$result = $query->execute( array( $user, $app ));

		$keys = array();
		while( $row = $result->fetchRow()){
			$keys[] = $row["configkey"];
		}

		return $keys;
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
	public static function getValue( $user, $app, $key, $default = null ){
		// Try to fetch the value, return default if not exists.
		$query = OC_DB::prepare( 'SELECT configvalue FROM *PREFIX*preferences WHERE userid = ? AND appid = ? AND configkey = ?' );
		$result = $query->execute( array( $user, $app, $key ));
		
		$row = $result->fetchRow();
		if($row){
			return $row["configvalue"];
		}else{
			return $default;
		}
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
	public static function setValue( $user, $app, $key, $value ){
		// Check if the key does exist
		$query = OC_DB::prepare( 'SELECT configvalue FROM *PREFIX*preferences WHERE userid = ? AND appid = ? AND configkey = ?' );
		$values=$query->execute(array($user,$app,$key))->fetchAll();
		$exists=(count($values)>0);

		if( !$exists ){
			$query = OC_DB::prepare( 'INSERT INTO *PREFIX*preferences ( userid, appid, configkey, configvalue ) VALUES( ?, ?, ?, ? )' );
			$query->execute( array( $user, $app, $key, $value ));
		}
		else{
			$query = OC_DB::prepare( 'UPDATE *PREFIX*preferences SET configvalue = ? WHERE userid = ? AND appid = ? AND configkey = ?' );
			$query->execute( array( $value, $user, $app, $key ));
		}
	}

	/**
	 * @brief Deletes a key
	 * @param $user user
	 * @param $app app
	 * @param $key key
	 * @returns true/false
	 *
	 * Deletes a key.
	 */
	public static function deleteKey( $user, $app, $key ){
		// No need for more comments
		$query = OC_DB::prepare( 'DELETE FROM *PREFIX*preferences WHERE userid = ? AND appid = ? AND configkey = ?' );
		$result = $query->execute( array( $user, $app, $key ));

		return true;
	}

	/**
	 * @brief Remove app of user from preferences
	 * @param $user user
	 * @param $app app
	 * @returns true/false
	 *
	 * Removes all keys in appconfig belonging to the app and the user.
	 */
	public static function deleteApp( $user, $app ){
		// No need for more comments
		$query = OC_DB::prepare( 'DELETE FROM *PREFIX*preferences WHERE userid = ? AND appid = ?' );
		$result = $query->execute( array( $user, $app ));

		return true;
	}

	/**
	 * @brief Remove user from preferences
	 * @param $user user
	 * @returns true/false
	 *
	 * Removes all keys in appconfig belonging to the user.
	 */
	public static function deleteUser( $user ){
		// No need for more comments
		$query = OC_DB::prepare( 'DELETE FROM *PREFIX*preferences WHERE userid = ?' );
		$result = $query->execute( array( $user ));

		return true;
	}

	/**
	 * @brief Remove app from all users
	 * @param $app app
	 * @returns true/false
	 *
	 * Removes all keys in preferences belonging to the app.
	 */
	public static function deleteAppFromAllUsers( $app ){
		// No need for more comments
		$query = OC_DB::prepare( 'DELETE FROM *PREFIX*preferences WHERE appid = ?' );
		$result = $query->execute( array( $app ));

		return true;
	}
}
