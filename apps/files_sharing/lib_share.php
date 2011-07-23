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

OC_HOOK::connect("OC_FILESYSTEM","post_delete", "OC_SHARE", "deleteItem");
OC_HOOK::connect("OC_FILESYSTEM","post_rename", "OC_SHARE", "renameItem");

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
	public function __construct($source, $uid_shared_with, $permissions, $public = false) {
		if ($source && OC_FILESYSTEM::file_exists($source) && OC_FILESYSTEM::is_readable($source)) {
			$source = "/".OC_USER::getUser()."/files".$source;
			$uid_owner = OC_USER::getUser();
			if ($public) {
				// TODO create token for public file
				$token = sha1("$uid_owner-$item");
			} else { 
				$query = OC_DB::prepare("INSERT INTO *PREFIX*sharing VALUES(?,?,?,?,?)");
				foreach ($uid_shared_with as $uid) {
					$target = "/".$uid."/files/Share/".basename($source);
					$check = OC_DB::prepare("SELECT target FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with = ?");
					$result = $check->execute(array($target, $uid))->fetchAll();
					// Check if target already exists for the user, if it does append a number to the name
					if (count($result) > 0) {
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
							$result = $check->execute(array($newTarget, $uid))->fetchAll();
							$counter++;
						}
						$target = $newTarget;
					}
					$query->execute(array($uid_owner, $uid, $source, $target, $permissions));
				}
			}
		}
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
		$query->execute(array($item[0]['uid_owner'], OC_USER::getUser(), $source, $newTarget, $item[0]['is_writeable']));
	}

	/**
	 * Get the item with the specified target location
	 * @param $target The target location of the item
	 * @return An array with the item
	 */
	public static function getItem($target) {
		$query = OC_DB::prepare("SELECT uid_owner, source, is_writeable  FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with = ? LIMIT 1");
		return $query->execute(array($target, OC_USER::getUser()))->fetchAll();
	}
	
	/**
	 * Get all items the current user is sharing
	 * @return An array with all items the user is sharing
	 */
	public static function getMySharedItems() {
		$query = OC_DB::prepare("SELECT uid_shared_with, source, is_writeable FROM *PREFIX*sharing WHERE uid_owner = ?");
		return $query->execute(array(OC_USER::getUser()))->fetchAll();
	}
	
	/**
	 * Get the items within a shared folder that have their own entry for the purpose of name, location, or permissions that differ from the folder itself
	 *
	 * Also can be used for getting all items shared with you e.g. pass '/MTGap/files'
	 *
	 * @param $targetFolder The target folder of the items to look for
	 * @return An array with all items in the database that are in the target folder
	 */
	public static function getItemsInFolder($targetFolder) {
		// Append '/' in order to filter out the folder itself if not already there
		if (substr($targetFolder, -1) !== "/") {
			$targetFolder .= "/";
		}
		// Remove any duplicate '/'
		$targetFolder = preg_replace('{(/)\1+}', "/", $targetFolder);
		$query = OC_DB::prepare("SELECT uid_owner, source, target FROM *PREFIX*sharing WHERE SUBSTR(target, 1, ?) = ? AND uid_shared_with = ?");
		return $query->execute(array(strlen($targetFolder), $targetFolder, OC_USER::getUser()))->fetchAll();
	}
	
	/**
	 * Get the source and target parent folders of the specified target location
	 * @param $target The target location of the item
	 * @return An array with the keys 'source' and 'target' with the values of the source and target parent folders
	 */
	public static function getParentFolders($target) {
		// Remove any duplicate or trailing '/'
		$target = rtrim($target, "/");
		$target = preg_replace('{(/)\1+}', "/", $target);
		$query = OC_DB::prepare("SELECT source FROM *PREFIX*sharing WHERE SUBSTR(target, 1, ?) = ? AND uid_shared_with = ? LIMIT 1");
		// Prevent searching for user directory e.g. '/MTGap/files'
		$userDirectory = substr($target, 0, strpos($target, "files") + 5);
		while ($target != "" && $target != "/" && $target != "." && $target != $userDirectory) {
			// Check if the parent directory of this target location is shared
			$target = dirname($target);
			$result = $query->execute(array(strlen($target), $target, OC_USER::getUser()))->fetchAll();
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
		// Remove any duplicate or trailing '/'
		$target = rtrim($target, "/");
		$target = preg_replace('{(/)\1+}', "/", $target);
		$query = OC_DB::prepare("SELECT source FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with = ? LIMIT 1");
		$result = $query->execute(array($target, OC_USER::getUser()))->fetchAll();
		if (count($result) > 0) {
			return $result[0]['source'];
		} else {
			$folders = self::getParentFolders($target);
			if ($folders == false) {
				return false;
			} else {
				return $folders['source'].substr($target, strlen($folders['target']));
			}
		}
	}

	/**
	 * Check if the user has write permission for the item at the specified target location
	 * @param $target The target location of the item
	 * @return True if the user has write permission or false if read only
	 */
	public static function isWriteable($target) {
		$query = OC_DB::prepare("SELECT is_writeable FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with = ? LIMIT 1");
		$result = $query->execute(array($target, OC_USER::getUser()))->fetchAll();
		if (count($result) > 0) {
			return $result[0]['is_writeable'];
		} else {
			// Check if the folder is writeable
			$folders = OC_SHARE::getParentFolders($target);
			$result = $query->execute(array($folders['target'], OC_USER::getUser()))->fetchAll();
			if (count($result) > 0) {
				return $result[0]['is_writeable'];
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
		$query = OC_DB::prepare("UPDATE *PREFIX*sharing SET source = REPLACE(source, ?, ?) WHERE uid_owner = ?");
		$query->execute(array($oldSource, $newSource, OC_USER::getUser()));
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
		$query = OC_DB::prepare("UPDATE *PREFIX*sharing SET target = REPLACE(target, ?, ?) WHERE uid_shared_with = ?");
		$query->execute(array($oldTarget, $newTarget, OC_USER::getUser()));
	}
	
	/**
	* Change write permission for the specified item and user
	*
	* You must construct a new shared item to change the write permission of a file inside a shared folder if the write permission differs from the folder
	*
	* @param $source The source location of the item
	* @param $uid_shared_with Array of users to change the write permission for
	* @param $is_writeable True if the user has write permission or false if read only
	*/
	public static function setIsWriteable($source, $uid_shared_with, $is_writeable) {
		$query = OC_DB::prepare("UPDATE *PREFIX*sharing SET is_writeable = ? WHERE SUBSTR(source, 1, ?) = ? AND uid_shared_with = ? AND uid_owner = ?");
		foreach ($uid_shared_with as $uid) {
			$query->execute(array($is_writeable, strlen($source), $source, $uid_shared_with, OC_USER::getUser()));
		}
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
		$query = OC_DB::prepare("DELETE FROM *PREFIX*sharing WHERE source SUBSTR(source, 1, ?) = ? AND uid_shared_with = ? AND uid_owner = ?");
		foreach ($uid_shared_with as $uid) {
			$query->execute(array(strlen($source), $source, $uid, OC_USER::getUser()));
		}
	}
	
	/**
	* Unshare the item from the current user, removes it only from the database and doesn't touch the source file
	*
	* You must use the pullOutOfFolder() function to unshare a file inside a shared folder and set $newTarget to nothing
	*
	* @param $target The target location of the item
	*/
	public static function unshareFromMySelf($target) {
		$query = OC_DB::prepare("DELETE FROM *PREFIX*sharing WHERE SUBSTR(target, 1, ?) = ? AND uid_shared_with = ?");
		$query->execute(array(strlen($target), $target, OC_USER::getUser()));
	}

	/**
	* Remove the item from the database, the owner deleted the file
	* @param $arguments Array of arguments passed from OC_HOOK
	*/
	public static function deleteItem($arguments) {
		$source = "/".OC_USER::getUser()."/files".$arguments['path'];
		$query = OC_DB::prepare("DELETE FROM *PREFIX*sharing WHERE SUBSTR(source, 1, ?) = ? AND uid_owner = ?");
		$query->execute(array(strlen($source), $source, OC_USER::getUser()));
	}

	/**
	* Rename the item in the database, the owner renamed the file
	* @param $arguments Array of arguments passed from OC_HOOK
	*/
	public static function renameItem($arguments) {
		$oldSource = "/".OC_USER::getUser()."/files".$arguments['oldpath'];
		$newSource = "/".OC_USER::getUser()."/files".$arguments['newpath'];
		self::setSource($oldSource, $newSource);
	}

}

?>
