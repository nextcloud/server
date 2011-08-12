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

OC_Hook::connect("OC_FILESYSTEM","post_delete", "OC_Share", "deleteItem");
OC_Hook::connect("OC_FILESYSTEM","post_rename", "OC_Share", "renameItem");

/**
 * This class manages shared items within the database. 
 */
class OC_Share {

	const WRITE = 1;
	const DELETE = 2;
      
	/**
	 * Share an item, adds an entry into the database
	 * @param $source The source location of the item
	 * @param $uid_shared_with The user to share the item with
	 * @param $permissions The permissions, use the constants WRITE and DELETE
	 */
	public function __construct($source, $uid_shared_with, $permissions) {
		$uid_owner = OC_User::getUser();
		$target = "/".$uid_shared_with."/files/Share/".basename($source);
		// Check if this item is already shared with the user
		$checkSource = OC_DB::prepare("SELECT source FROM *PREFIX*sharing WHERE source = ? AND uid_shared_with = ?");
		$resultCheckSource = $checkSource->execute(array($source, $uid_shared_with))->fetchAll();
		// TODO Check if the source is inside a folder
		if (count($resultCheckSource) > 0) {
			throw new Exception("This item is already shared with the specified user");
		}
		// Check if target already exists for the user, if it does append a number to the name
		$checkTarget = OC_DB::prepare("SELECT target FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with = ?");
		$resultCheckTarget = $checkTarget->execute(array($target, $uid_shared_with))->fetchAll();
		if (count($resultCheckTarget) > 0) {
			if ($pos = strrpos($target, ".")) {
				$name = substr($target, 0, $pos);
				$ext = substr($target, $pos);
			} else {
				$name = $target;
				$ext = "";
			}
			$counter = 1;
			while (count($result) > 0) {
				$newTarget = $name."_".$counter.$ext;
				$resultCheckTarget = $checkTarget->execute(array($newTarget, $uid_shared_with))->fetchAll();
				$counter++;
			}
			$target = $newTarget;
		}
		$query = OC_DB::prepare("INSERT INTO *PREFIX*sharing VALUES(?,?,?,?,?)");
		$query->execute(array($uid_owner, $uid_shared_with, $source, $target, $permissions));
	}

	/**
	* Remove any duplicate or trailing '/' from the path
	* @return A clean path
	*/
	private static function cleanPath($path) {
		$path = rtrim($path, "/");
		return preg_replace('{(/)\1+}', "/", $path);
	}

	/**
	* Get the user and the user's groups and put them into an array
	* @return An array to be used by the IN operator in a query for uid_shared_with
	*/
	private static function getUserAndGroups() {
		$self = OC_User::getUser();
		$groups = OC_Group::getUserGroups($self);
		array_unshift($groups, $self);
		return $groups;
	}

	/**
	 * Create a new entry in the database for a file inside a shared folder
	 *
	 * $oldTarget and $newTarget may be the same value. $oldTarget exists in case the file is being moved outside of the folder
	 *
	 * @param $oldTarget The current target location
	 * @param $newTarget The new target location
	 */
	public static function pullOutOfFolder($oldTarget, $newTarget) {
		$folders = self::getParentFolders($oldTarget);
		$source = $folders['source'].substr($oldTarget, strlen($folders['target']));
		$item = self::getItem($folders['target']);
		$query = OC_DB::prepare("INSERT INTO *PREFIX*sharing VALUES(?,?,?,?,?)");
		$query->execute(array($item[0]['uid_owner'], OC_User::getUser(), $source, $newTarget, $item[0]['permissions']));
	}

	/**
	 * Get the item with the specified target location
	 * @param $target The target location of the item
	 * @return An array with the item
	 */
	public static function getItem($target) {
		$target = self::cleanPath($target);
		$query = OC_DB::prepare("SELECT uid_owner, source, permissions FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with = ? LIMIT 1");
		return $query->execute(array($target, OC_User::getUser()))->fetchAll();
	}

	 /**
	 * Get the item with the specified source location
	 * @param $source The source location of the item
	 * @return An array with the users and permissions the item is shared with
	 */
	public static function getMySharedItem($source) {
		$source = self::cleanPath($source);
		$query = OC_DB::prepare("SELECT uid_shared_with, permissions FROM *PREFIX*sharing WHERE source = ? AND uid_owner = ?");
		return $query->execute(array($source, OC_User::getUser()))->fetchAll();
	}
	/**
	 * Get all items the current user is sharing
	 * @return An array with all items the user is sharing
	 */
	public static function getMySharedItems() {
		$query = OC_DB::prepare("SELECT uid_shared_with, source, permissions FROM *PREFIX*sharing WHERE uid_owner = ?");
		return $query->execute(array(OC_User::getUser()))->fetchAll();
	}
	
	/**
	 * Get the items within a shared folder that have their own entry for the purpose of name, location, or permissions that differ from the folder itself
	 *
	 * Works for both target and source folders. Can be used for getting all items shared with you e.g. pass '/MTGap/files'
	 *
	 * @param $folder The folder of the items to look for
	 * @return An array with all items in the database that are in the folder
	 */
	public static function getItemsInFolder($folder) {
		$folder = self::cleanPath($folder);
		// Append '/' in order to filter out the folder itself if not already there
		if (substr($folder, -1) !== "/") {
			$folder .= "/";
		}
		$length = strlen($folder);
		$userAndGroups = self::getUserAndGroups();
		$query = OC_DB::prepare("SELECT uid_owner, source, target FROM *PREFIX*sharing WHERE SUBSTR(source, 1, ?) = ? OR SUBSTR(target, 1, ?) = ? AND uid_shared_with IN(".substr(str_repeat(",?", count($userAndGroups)), 1).")");
		return $query->execute(array_merge(array($length, $folder, $length, $folder), $userAndGroups))->fetchAll();
	}
	
	/**
	 * Get the source and target parent folders of the specified target location
	 * @param $target The target location of the item
	 * @return An array with the keys 'source' and 'target' with the values of the source and target parent folders
	 */
	public static function getParentFolders($target) {
		$target = self::cleanPath($target);
		$userAndGroups = self::getUserAndGroups();
		$query = OC_DB::prepare("SELECT source FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with IN(".substr(str_repeat(",?", count($userAndGroups)), 1).") LIMIT 1");
		// Prevent searching for user directory e.g. '/MTGap/files'
		$userDirectory = substr($target, 0, strpos($target, "files") + 5);
		while ($target != "" && $target != "/" && $target != "." && $target != $userDirectory) {
			// Check if the parent directory of this target location is shared
			$target = dirname($target);
			$result = $query->execute(array_merge(array($target), $userAndGroups))->fetchAll();
			if (count($result) > 0) {
				break;
			}
		}
		if (count($result) > 0) {
			// Return both the source folder and the target folder
			return array("source" => $result[0]['source'], "target" => $target);
		} else {
			return false;
		}
	}

	/**
	 * Get the source location of the item at the specified target location
	 * @param $target The target location of the item
	 * @return Source location or false if target location is not valid
	 */
	public static function getSource($target) {
		$target = self::cleanPath($target);
		$userAndGroups = self::getUserAndGroups();
		$query = OC_DB::prepare("SELECT source FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with IN(".substr(str_repeat(",?", count($userAndGroups)), 1).") LIMIT 1");
		$result = $query->execute(array_merge(array($target), $userAndGroups))->fetchAll();
		if (count($result) > 0) {
			return $result[0]['source'];
		} else {
			$folders = self::getParentFolders($target);
			if ($folders == true) {
				return $folders['source'].substr($target, strlen($folders['target']));
			} else {
				return false;
			}
		}
	}

	/**
	 * Get the user's permissions for the item at the specified target location
	 * @param $target The target location of the item
	 * @return The permissions, use bitwise operators to check against the constants WRITE and DELETE
	 */
	public static function getPermissions($target) {
		$target = self::cleanPath($target);
		$userAndGroups = self::getUserAndGroups();
		$query = OC_DB::prepare("SELECT permissions FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with IN(".substr(str_repeat(",?", count($userAndGroups)), 1).") LIMIT 1");
		$result = $query->execute(array_merge(array($target), $userAndGroups))->fetchAll();
		if (count($result) > 0) {
			return $result[0]['permissions'];
		} else {
			$folders =self::getParentFolders($target);
			if ($folders == true) {
				$result = $query->execute(array_merge(array($folders), $userAndGroups))->fetchAll();
				if (count($result) > 0) {
					return $result[0]['permissions'];
				}
			} else {
				return false;
			}
		}
	}

	/**
	 * Set the source location to a new value
	 * @param $oldSource The current source location
	 * @param $newTarget The new source location
	 */
	public static function setSource($oldSource, $newSource) {
		$oldSource = self::cleanPath($oldSource);
		$newSource = self::cleanPath($newSource);
		$query = OC_DB::prepare("UPDATE *PREFIX*sharing SET source = REPLACE(source, ?, ?) WHERE uid_owner = ?");
		$query->execute(array($oldSource, $newSource, OC_User::getUser()));
	}
	
	/**
	 * Set the target location to a new value
	 *
	 * You must use the pullOutOfFolder() function to change the target location of a file inside a shared folder if the target location differs from the folder
	 *
	 * @param $oldTarget The current target location
	 * @param $newTarget The new target location 
	 */
	public static function setTarget($oldTarget, $newTarget) {
		$oldTarget = self::cleanPath($oldTarget);
		$newTarget = self::cleanPath($newTarget);
		$query = OC_DB::prepare("UPDATE *PREFIX*sharing SET target = REPLACE(target, ?, ?) WHERE uid_shared_with = ?");
		$query->execute(array($oldTarget, $newTarget, OC_User::getUser()));
	}
	
	/**
	* Change the permissions for the specified item and user
	*
	* You must construct a new shared item to change the permissions of a file inside a shared folder if the permissions differ from the folder
	*
	* @param $source The source location of the item
	* @param $uid_shared_with The user to change the permissions for
	* @param $permissions The permissions, use the constants WRITE and DELETE
	*/
	public static function setPermissions($source, $uid_shared_with, $permissions) {
		$source = self::cleanPath($source);
		$query = OC_DB::prepare("UPDATE *PREFIX*sharing SET permissions = ? WHERE SUBSTR(source, 1, ?) = ? AND uid_shared_with = ? AND uid_owner = ?");
		$query->execute(array($permissions, strlen($source), $source, $uid_shared_with, OC_User::getUser()));
	}
	
	/**
	* Unshare the item, removes it from all specified users
	*
	* You must use the pullOutOfFolder() function to unshare a file inside a shared folder and set $newTarget to nothing
	*
	* @param $source The source location of the item
	* @param $uid_shared_with Array of users to unshare the item from
	*/
	public static function unshare($source, $uid_shared_with) {
		$source = self::cleanPath($source);
		$query = OC_DB::prepare("DELETE FROM *PREFIX*sharing WHERE SUBSTR(source, 1, ?) = ? AND uid_shared_with = ? AND uid_owner = ?");
		$query->execute(array(strlen($source), $source, $uid_shared_with, OC_User::getUser()));
	}
	
	/**
	* Unshare the item from the current user, removes it only from the database and doesn't touch the source file
	*
	* You must use the pullOutOfFolder() function to unshare a file inside a shared folder and set $newTarget to nothing
	*
	* @param $target The target location of the item
	*/
	public static function unshareFromMySelf($target) {
		$target = self::cleanPath($target);
		$query = OC_DB::prepare("DELETE FROM *PREFIX*sharing WHERE SUBSTR(target, 1, ?) = ? AND uid_shared_with = ?");
		$query->execute(array(strlen($target), $target, OC_User::getUser()));
	}

	/**
	* Remove the item from the database, the owner deleted the file
	* @param $arguments Array of arguments passed from OC_HOOK
	*/
	public static function deleteItem($arguments) {
		$source = "/".OC_User::getUser()."/files".$arguments['path'];
		$source = self::cleanPath($source);
		$query = OC_DB::prepare("DELETE FROM *PREFIX*sharing WHERE SUBSTR(source, 1, ?) = ? AND uid_owner = ?");
		$query->execute(array(strlen($source), $source, OC_User::getUser()));
	}

	/**
	* Rename the item in the database, the owner renamed the file
	* @param $arguments Array of arguments passed from OC_HOOK
	*/
	public static function renameItem($arguments) {
		$oldSource = "/".OC_User::getUser()."/files".$arguments['oldpath'];
		$oldSource = self::cleanPath($oldSource);
		$newSource = "/".OC_User::getUser()."/files".$arguments['newpath'];
		$newSource = self::cleanPath($newSource);
		self::setSource($oldSource, $newSource);
	}

}

?>
