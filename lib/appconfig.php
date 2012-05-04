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
 * `configkey` VARCHAR( 255 ) NOT NULL ,
 * `configvalue` VARCHAR( 255 ) NOT NULL
 * )
 *
 */

/**
 * This class provides an easy way for apps to store config values in the
 * database.
 */
class OC_Appconfig{
	/**
	 * @brief Get all apps using the config
	 * @returns array with app ids
	 *
	 * This function returns a list of all apps that have at least one
	 * entry in the appconfig table.
	 */
	public static function getApps(){
		// No magic in here!
		$query = OC_DB::prepare( 'SELECT DISTINCT appid FROM *PREFIX*appconfig' );
		$result = $query->execute();

		$apps = array();
		while( $row = $result->fetchRow()){
			$apps[] = $row["appid"];
		}

		return $apps;
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
		// No magic in here as well
		$query = OC_DB::prepare( 'SELECT configkey FROM *PREFIX*appconfig WHERE appid = ?' );
		$result = $query->execute( array( $app ));

		$keys = array();
		while( $row = $result->fetchRow()){
			$keys[] = $row["configkey"];
		}

		return $keys;
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
		// At least some magic in here :-)
		$query = OC_DB::prepare( 'SELECT configvalue FROM *PREFIX*appconfig WHERE appid = ? AND configkey = ?' );
		$result = $query->execute( array( $app, $key ));
		$row = $result->fetchRow();
		if($row){
			return $row["configvalue"];
		}else{
			return $default;
		}
	}
	
	/**
	 * @brief check if a key is set in the appconfig
	 * @param string $app
	 * @param string $key
	 * @return bool
	 */
	public static function hasKey($app,$key){
		$exists = self::getKeys( $app );
		return in_array( $key, $exists );
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
		// Does the key exist? yes: update. No: insert
		if(! self::hasKey($app,$key)){
			$query = OC_DB::prepare( 'INSERT INTO *PREFIX*appconfig ( appid, configkey, configvalue ) VALUES( ?, ?, ? )' );
			$query->execute( array( $app, $key, $value ));
		}
		else{
			$query = OC_DB::prepare( 'UPDATE *PREFIX*appconfig SET configvalue = ? WHERE appid = ? AND configkey = ?' );
			$query->execute( array( $value, $app, $key ));
		}
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
		// Boring!
		$query = OC_DB::prepare( 'DELETE FROM *PREFIX*appconfig WHERE appid = ? AND configkey = ?' );
		$query->execute( array( $app, $key ));

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
		// Nothing special
		$query = OC_DB::prepare( 'DELETE FROM *PREFIX*appconfig WHERE appid = ?' );
		$query->execute( array( $app ));

		return true;
	}
	
	/**
	 * get multiply values, either the app or key can be used as wildcard by setting it to false
	 * @param app
	 * @param key
	 * @return array
	 */
	public static function getValues($app,$key){
		if($app!==false and $key!==false){
			return false;
		}
		$where='WHERE';
		$fields='configvalue';
		$params=array();
		if($app!==false){
			$where.=' appid = ?';
			$fields.=', configkey';
			$params[]=$app;
			$key='configkey';
		}else{
			$fields.=', appid';
			$where.=' configkey = ?';
			$params[]=$key;
			$key='appid';
		}
		$queryString='SELECT '.$fields.' FROM *PREFIX*appconfig '.$where;
		$query=OC_DB::prepare($queryString);
		$result=$query->execute($params);
		$values=array();
		while($row=$result->fetchRow()){
			$values[$row[$key]]=$row['configvalue'];
		}
		return $values;
	}
}
