<?php
/**
 * ownCloud
 *
 * @author Michael Gapczynski
 * @copyright 2011 Michael Gapczynski GapczynskiM@gmail.com
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
 * This class manages shared items within the database. 
 */
class OC_SHARE {
	
	/**
	 * TODO notify user a file is being shared with them?
	 * Share an item, adds an entry into the database
	 * @param string $item
	 * @param user item shared with $uid_shared_with
	 */
	public function __construct($item, $public = false, $uid_shared_with) {
		if ($item && OC_FILESYSTEM::file_exists($item) && OC_FILESYSTEM::is_readable($item)) {
			$uid_owner = $_SESSION['user_id'];
			if ($public) {
				// TODO create token for public file
				$token = sha1("$uid_owner-$item");
			} else { 
				$query = OC_DB::prepare("INSERT INTO *PREFIX*sharing VALUES(?,?,?,?)");
				foreach ($uid_shared_with as $uid) {
					$query->execute(array($uid_owner, $uid, $item));
				}
			}
		}
	}
	
	/**
	* TODO complete lib_permissions
	* Change the permissions of the specified item
	* @param permissions $permissions
	*/
	public static function setPermissions($item, $uid_shared_with, $permissions) {
		$query = OC_DB::prepare("UPDATE *PREFIX*sharing SET permissions = ? WHERE item = ? AND uid_shared_with = ? AND uid_owner = ?");
		$query->execute(array($permissions, $item, $uid_shared_with, $_SESSION['user_id']));
	}
	
	/**
	 * Get the permissions for the specified item
	 * @param unknown_type $item
	 */
	public static function getPermissions($item, $uid_shared_with) {
		$query = OC_DB::prepare("SELECT permissions FROM *PREFIX*sharing WHERE item = ? AND uid_shared_with = ? AND uid_owner = ? ");
		return $query->execute(array($item, $uid_shared_with, $_SESSION['user_id']))->fetchAll();
	}
	
	/**
	* Unshare the item, removes it from all users specified
	* @param array $uid_shared_with
	*/
	public static function unshare($item, $uid_shared_with) {
		$query = OC_DB::prepare("DELETE FROM *PREFIX*sharing WHERE item = ? AND uid_shared_with = ? AND uid_owner = ?");
		foreach ($uid_shared_with as $uid) {
			$query->execute(array($item, $uid, $_SESSION['user_id']));
		}
	}
	
	/**
	 * Get the source location of the target item
	 * @return source path
	 */
	public static function getSource($target) {
		$query = OC_DB::prepare("SELECT source FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with = ?");
		$result = $query->execute(array($target, $_SESSION['user_id']))->fetchAll();
		return $result[0]['source'];
	}
	
	/**
	 * Get all items the user is sharing
	 * @return array
	 */
	public static function getSharedItems() {
		$query = OC_DB::prepare("SELECT * FROM *PREFIX*sharing WHERE uid_owner = ?");
		return $query->execute(array($_SESSION['user_id']))->fetchAll();
	}
	
	/**
	 * Get all items shared with the user
	 * @return array
	 */
	public static function getItemsSharedWith() {
		$query = OC_DB::prepare("SELECT * FROM *PREFIX*sharing WHERE uid_shared_with = ?");
		return $query->execute(array($_SESSION['user_id']))->fetchAll();
	}
	
}

?>
