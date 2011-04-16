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
 * CREATE TABLE `groups` (
 *   `gid` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
 *   PRIMARY KEY (`gid`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
 *
 * CREATE TABLE `group_user` (
 *   `gid` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
 *   `uid` varchar(64) COLLATE utf8_unicode_ci NOT NULL
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
 *
 */

require_once( 'Group/backend.php' );

/**
 * Class for group management in a SQL Database (e.g. MySQL, SQLite)
 *
 */
class OC_GROUP_DATABASE extends OC_GROUP_BACKEND {
	static private $userGroupCache=array();

	/**
	 * Try to create a new group
	 *
	 * @param  string  $groupName  The name of the group to create
	 */
	public static function createGroup( $gid ){
		$query = OC_DB::prepare( "SELECT * FROM `*PREFIX*groups` WHERE `gid` = ?" );
		$result = $query->execute( array( $gid ));

		if( $result->numRows() > 0 ){
			return false;
		}
		else{
			$query = OC_DB::prepare( "INSERT INTO `*PREFIX*groups` ( `gid` ) VALUES( ? )" );
			$result = $query->execute( array( $gid ));

			return $result ? true : false;
		}
	}

	/**
	 * Check if a user belongs to a group
	 *
	 * @param  string  $username   Name of the user to check
	 * @param  string  $groupName  Name of the group
	 */
	public static function inGroup( $username, $groupName ){
		$query = OC_DB::prepare( "SELECT * FROM `*PREFIX*group_user` WHERE `gid` = ? AND `uid` = ?" );
		$result = $query->execute( array( $groupName, $username ));
		if( PEAR::isError($result)) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$result->getDebugInfo().'<br />';
			error_log( $entry );
			die( $entry );
		}
		return $result->numRows() > 0 ? true : false;
	}

	/**
	 * Add a user to a group
	 *
	 * @param  string  $username   Name of the user to add to group
	 * @param  string  $groupName  Name of the group in which add the user
	 */
	public static function addToGroup( $username, $groupName ){
		if( !self::inGroup( $username, $groupName )){
			$query = OC_DB::prepare( "INSERT INTO `*PREFIX*group_user` ( `uid`, `gid` ) VALUES( ?, ? )" );
			$result = $query->execute( array( $username, $groupName ));
		}
	}

	/**
	 * Remove a user from a group
	 *
	 * @param  string  $username   Name of the user to remove from group
	 * @param  string  $groupName  Name of the group from which remove the user
	 */
	public static function removeFromGroup( $username, $groupName ){
		$query = OC_DB::prepare( "DELETE FROM `*PREFIX*group_user` WHERE `uid` = ? AND `gid` = ?" );
		$result = $query->execute( array( $username, $groupName ));
	}

	/**
	 * Get all groups the user belongs to
	 *
	 * @param  string  $username  Name of the user
	 */
	public static function getUserGroups( $username ){
		$query = OC_DB::prepare( "SELECT * FROM `*PREFIX*group_user` WHERE `uid` = ?" );
		$result = $query->execute( array( $username ));

		$groups = array();
		while( $row = $result->fetchRow()){
			$groups[] = $row["gid"];
		}

		return $groups;
	}

	/**
	 * get a list of all groups
	 *
	 */
	public static function getGroups(){
		$query = OC_DB::prepare( "SELECT * FROM `*PREFIX*groups`" );
		$result = $query->execute();

		$groups = array();
		while( $row = $result->fetchRow()){
			$groups[] = $row;
		}

		return $groups;
	}
}
