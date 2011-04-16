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
/*
 *
 * The following SQL statement is just a help for developers and will not be
 * executed!
 *
 * CREATE TABLE `users` (
 *   `uid` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
 *   `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 *   PRIMARY KEY (`uid`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
 *
 */

require_once('User/backend.php');

/**
 * Class for user management in a SQL Database (e.g. MySQL, SQLite)
 *
 */
class OC_USER_DATABASE extends OC_USER_BACKEND {
	static private $userGroupCache=array();

	/**
	 * Try to create a new user
	 *
	 * @param  string  $username  The username of the user to create
	 * @param  string  $password  The password of the new user
	 */
	public static function createUser( $uid, $password ){
		$query = OC_DB::prepare( "SELECT * FROM `*PREFIX*users` WHERE `uid` = ?" );
		$result = $query->execute( array( $uid ));

		// Check if the user already exists
		if ( $result->numRows() > 0 ){
			return false;
		}
		else{
			$query = OC_DB::prepare( "INSERT INTO `*PREFIX*users` ( `uid`, `password` ) VALUES( ?, ? )" );
			$result = $query->execute( array( $uid, sha1( $password )));

			return $result ? true : false;
		}
	}

	/**
	 * Try to login a user
	 *
	 * @param  string  $username  The username of the user to log in
	 * @param  string  $password  The password of the user
	 */
	public static function login( $username, $password ){
		$query = OC_DB::prepare( "SELECT `uid` FROM `*PREFIX*users` WHERE `uid` = ? AND `password` = ?" );
		$result = $query->execute( array( $username, sha1( $password )));

		if( $result->numRows() > 0 ){
			$row = $result->fetchRow();
			$_SESSION['user_id'] = $row["uid"];
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Kick the user
	 *
	 */
	public static function logout() {
		OC_LOG::add( "core", $_SESSION['user_id'], "logout" );
		$_SESSION['user_id'] = false;
	}

	/**
	 * Check if the user is logged in
	 *
	 */
	public static function isLoggedIn() {
		if( isset($_SESSION['user_id']) AND $_SESSION['user_id'] ){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Generate a random password
	 */
	public static function generatePassword(){
		return uniqId();
	}

	/**
	 * Set the password of a user
	 *
	 * @param  string  $username  User who password will be changed
	 * @param  string  $password  The new password for the user
	 */
	public static function setPassword( $username, $password ){
		$query = OC_DB::prepare( "UPDATE `*PREFIX*users` SET `password` = ? WHERE `uid` = ?" );
		$result = $query->execute( array( sha1( $password ), $username ));

		if( $result->numRows() > 0 ){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Check if the password of the user is correct
	 *
	 * @param  string  $username  Name of the user
	 * @param  string  $password  Password of the user
	 */
	public static function checkPassword( $username, $password ){
		$query = OC_DB::prepare( "SELECT `uid` FROM `*PREFIX*users` WHERE `uid` = ? AND `password` = ?" );
		$result = $query->execute( array( $username, sha1( $password )));

		if( $result->numRows() > 0 ){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * get a list of all users
	 *
	 */
	public static function getUsers(){
		$query = OC_DB::prepare( "SELECT `uid` FROM `*PREFIX*users`" );
		$result = $query->execute();

		$users=array();
		while( $row = $result->fetchRow()){
			$users[] = $row["uid"];
		}
		return $users;
	}
}
