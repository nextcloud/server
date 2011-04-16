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
 * CREATE TABLE `log` (
 * `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 * `timestamp` DATETIME NOT NULL ,
 * `appid` VARCHAR( 255 ) NOT NULL ,
 * `user` VARCHAR( 255 ) NOT NULL ,
 * `action` VARCHAR( 255 ) NOT NULL ,
 * `info` TEXT NOT NULL
 * ) ENGINE = MYISAM ;
 *
 */

/**
 * This class is for logging
 */
class OC_LOG {
	/**
	 * @brief adds an entry to the log
	 * @param $appid id of the app
	 * @param $subject username
	 * @param $predicate action
	 * @param $object = null; additional information
	 * @returns true/false
	 *
	 * This function adds another entry to the log database
	 */
	public static function add( $subject, $predicate, $object = null ){
		// TODO: write function
		return true;
	}

	/**
	 * @brief Fetches log entries
	 * @param $filter = array(); array with filter options
	 * @returns array with entries
	 *
	 * This function fetches the log entries according to the filter options
	 * passed.
	 *
	 * $filter is an associative array.
	 * The following keys are optional:
	 *   - from: all entries after this date
	 *   - until: all entries until this date
	 *   - user: username (default: current user)
	 *   - app: only entries for this app
	 */
	public static function get( $filter = array()){
		// TODO: write function
		return array();
	}

	/**
	 * @brief removes log entries
	 * @param $date delete entries older than this date
	 * @returns true/false
	 *
	 * This function deletes all entries that are older than $date.
	 */
	public static function deleteBefore( $date ){
		// TODO: write function
		return true;
	}
}



?>
