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
	public function __construct($source, $uid_shared_with, $permissions, $public = false) {
		if ($source && OC_FILESYSTEM::file_exists($source) && OC_FILESYSTEM::is_readable($source)) {
			$uid_owner = $_SESSION['user_id'];
			if ($public) {
				// TODO create token for public file
				$token = sha1("$uid_owner-$item");
			} else { 
				$query = OC_DB::prepare("INSERT INTO *PREFIX*sharing VALUES(?,?,?,?,?)");
				$sourceLocalPath = substr($source, strlen("/".$uid_owner."/files/"));;
				foreach ($uid_shared_with as $uid) {
					// TODO check to see if target already exists in database
					$target = "/".$uid."/files/Share/".$sourceLocalPath;
					$query->execute(array($uid_owner, $uid, $source, $target, $permissions));
				}
			}
		}
	}
	
	/**
	* Change is writeable for the specified item and user
	* @param $source
	* @param $uid_shared_with
	* @param $is_writeable
	*/
	public static function setIsWriteable($source, $uid_shared_with, $is_writeable) {
		$query = OC_DB::prepare("UPDATE *PREFIX*sharing SET is_writeable = ? WHERE source COLLATE latin1_bin LIKE ? AND uid_shared_with = ? AND uid_owner = ?");
		$query->execute(array($is_writeable, $source."%", $uid_shared_with, $_SESSION['user_id']));
		if (mysql_affected_rows() == 0) {
			// A new entry is added to the database when a file within a shared folder is set new a value for is_writeable, but not the entire folder
			$query = OC_DB::prepare("INSERT INTO *PREFIX*sharing VALUES(?,?,?,?,?)");
			$target = "/".$uid_shared_with."/files/";
			$query->execute(array($_SESSION['user_id'], $uid_shared_with, $source, $target, $is_writeable));
		}
	}
	
	/**
	 * Check if the specified item is writeable for the user
	 * @param $target
	 * @return true or false
	 */
	public static function isWriteable($target) {
		$query = OC_DB::prepare("SELECT is_writeable FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with = ?");
		$result = $query->execute(array($target, $_SESSION['user_id']))->fetchAll();
		if (count($result) > 0) {
			return $result[0]['is_writeable'];
		} else {
			$folders = OC_SHARE::getParentFolders($target, false);
			$result = $query->execute(array($folders['target'], $_SESSION['user_id']))->fetchAll();
			if (count($result) > 0) {
				return $result[0]['is_writeable'];
			} else {
				return false;
			}
		}
	}
	
	/**
	* Unshare the item, removes it from all users specified
	* @param array $uid_shared_with
	*/
	public static function unshare($source, $uid_shared_with) {
		$query = OC_DB::prepare("DELETE FROM *PREFIX*sharing WHERE source = ? AND uid_shared_with = ? AND uid_owner = ?");
		foreach ($uid_shared_with as $uid) {
			$query->execute(array($source, $uid, $_SESSION['user_id']));
		}
	}
	
	/**
	* Unshare the item from the current user - used when the user deletes the item
	* @param $target
	*/
	public static function unshareFromSelf($target) {
		$query = OC_DB::prepare("DELETE FROM *PREFIX*sharing WHERE target COLLATE latin1_bin LIKE ? AND uid_shared_with = ?");
		$query->execute(array($target."%", $_SESSION['user_id']));
	}
	
	/**
	 * Set the source location to a new value
	 * @param $oldSource The current source location
	 * @param $newTarget The new source location
	 */
	public static function setSource($oldSource, $newSource) {
		$query = OC_DB::prepare("UPDATE *PREFIX*sharing SET source = REPLACE(source, ?, ?) WHERE uid_owner = ?");
		$query->execute(array($oldSource, $newSource, $_SESSION['user_id']));
	}
	
	/**
	 * Get the source location of the target item
	 * @param $target
	 * @return source path
	 */
	public static function getSource($target) {
		// Remove any trailing '/'
		$target = rtrim($target, "/");
		$query = OC_DB::prepare("SELECT source FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with = ? LIMIT 1");
		$result = $query->execute(array($target, $_SESSION['user_id']))->fetchAll();
		if (count($result) > 0) {
			return $result[0]['source'];
		} else {
			$folders = OC_SHARE::getParentFolders($target, false);
			return $folders['source'].substr($target, strlen($folders['target']));
		}
	}
	
	public static function getParentFolders($path, $isSource = true) {
		// Remove any trailing '/'
		$path = rtrim($path, "/");
		if ($isSource) {
			$query = OC_DB::prepare("SELECT target FROM *PREFIX*sharing WHERE source = ? AND uid_shared_with = ? LIMIT 1");
		} else {
			$query = OC_DB::prepare("SELECT source FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with = ? LIMIT 1");
		}
		// TODO Prevent searching for user directory e.g. '/MTGap/files'
		while ($path != "" && $path != "/" && $path != ".") {
			$result = $query->execute(array($path, $_SESSION['user_id']))->fetchAll();
			if (count($result) > 0) {
				break;
			} else {
				// Check if the parent directory of this target is shared
				$path = dirname($path);
			}
		}
		if (count($result) > 0) {
			if ($isSource) {
				$sourceFolder = $path;
				$targetFolder = $result[0]['target'];
			} else {
				$sourceFolder = $result[0]['source'];
				$targetFolder = $path;
			}
			// Return both the source folder and the target folder
			return array("source" => $sourceFolder, "target" => $targetFolder);
		} else {
			return false;
		}
	}
	
	/**
	 * Set the target location to a new value
	 * @param $oldTarget The current target location
	 * @param $newTarget The new target location 
	 */
	public static function setTarget($oldTarget, $newTarget) {
		$query = OC_DB::prepare("UPDATE *PREFIX*sharing SET target = REPLACE(target, ?, ?) WHERE uid_shared_with = ?");
		$query->execute(array($oldTarget, $newTarget, $_SESSION['user_id']));
		if (mysql_affected_rows() == 0) {
			// A new entry is added to the database when a file within a shared folder is renamed or is moved outside the original target folder
			$query = OC_DB::prepare("SELECT uid_owner, is_writeable FROM *PREFIX*sharing WHERE source = ? AND uid_shared_with = ? LIMIT 1");
			$folders = OC_SHARE::getParentFolders($oldTarget, false);
			$result = $query->execute(array($folders['source'], $_SESSION['user_id']));
			$query = OC_DB::prepare("INSERT INTO *PREFIX*sharing VALUES(?,?,?,?,?)");
			$query->execute(array($result[0]['uid_owner'], $_SESSION['user_id'], $folders['source'].substr($oldTarget, strlen($folders['target'])), $newTarget, $result[0]['is_writeable']));
		}
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
