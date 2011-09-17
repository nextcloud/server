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

/**
 * Class for user management in a SQL Database (e.g. MySQL, SQLite)
 */
class OC_User_Database extends OC_User_Backend {
	static private $userGroupCache=array();

	/**
	 * @brief Create a new user
	 * @param $uid The username of the user to create
	 * @param $password The password of the new user
	 * @returns true/false
	 *
	 * Creates a new user. Basic checking of username is done in OC_User
	 * itself, not in its subclasses.
	 */
	public function createUser( $uid, $password ){
		if( $this->userExists($uid) ){
			return false;
		}
		else{
			$query = OC_DB::prepare( "INSERT INTO `*PREFIX*users` ( `uid`, `password` ) VALUES( ?, ? )" );
			$result = $query->execute( array( $uid, sha1( $password )));

			return $result ? true : false;
		}
	}

	/**
	 * @brief delete a user
	 * @param $uid The username of the user to delete
	 * @returns true/false
	 *
	 * Deletes a user
	 */
	public function deleteUser( $uid ){
		// Delete user-group-relation
		$query = OC_DB::prepare( "DELETE FROM `*PREFIX*users` WHERE uid = ?" );
		$result = $query->execute( array( $uid ));
		return true;
	}

	/**
	 * @brief Set password
	 * @param $uid The username
	 * @param $password The new password
	 * @returns true/false
	 *
	 * Change the password of a user
	 */
	public function setPassword( $uid, $password ){
		if( $this->userExists($uid) ){
			$query = OC_DB::prepare( "UPDATE *PREFIX*users SET password = ? WHERE uid = ?" );
			$result = $query->execute( array( sha1( $password ), $uid ));

			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * @brief Check if the password is correct
	 * @param $uid The username
	 * @param $password The password
	 * @returns true/false
	 *
	 * Check if the password is correct without logging in the user
	 */
	public function checkPassword( $uid, $password ){
		$query = OC_DB::prepare( "SELECT uid FROM *PREFIX*users WHERE uid LIKE ? AND password = ?" );
		$result = $query->execute( array( $uid, sha1( $password )));

		$row=$result->fetchRow();
		if($row){
			return $row['uid'];
		}else{
			return false;
		}
	}

	/**
	 * @brief Get a list of all users
	 * @returns array with all uids
	 *
	 * Get a list of all users.
	 */
	public function getUsers(){
		$query = OC_DB::prepare( "SELECT uid FROM *PREFIX*users" );
		$result = $query->execute();

		$users=array();
		while( $row = $result->fetchRow()){
			$users[] = $row["uid"];
		}
		return $users;
	}

	/**
	 * @brief check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 */
	public function userExists($uid){
		$query = OC_DB::prepare( "SELECT * FROM `*PREFIX*users` WHERE uid = ?" );
		$result = $query->execute( array( $uid ));
		
		return $result->numRows() > 0;
	}
}
