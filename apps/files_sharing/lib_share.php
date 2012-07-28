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
class OC_Share {

	const WRITE = 1;
	const DELETE = 2;
	const UNSHARED = -1;
	const PUBLICLINK = "public";

	private $token;

	/**
	 * Share an item, adds an entry into the database
	 * @param $source The source location of the item
	 * @param $uid_shared_with The user or group to share the item with
	 * @param $permissions The permissions, use the constants WRITE and DELETE
	 */
	public function __construct($source, $uid_shared_with, $permissions) {
		$uid_owner = OCP\USER::getUser();
		$query = OCP\DB::prepare("INSERT INTO *PREFIX*sharing VALUES(?,?,?,?,?)");
		// Check if this is a reshare and use the original source
		if ($result = OC_Share::getSource($source)) {
			$source = $result;
		}
		if ($uid_shared_with == self::PUBLICLINK) {
			$token = sha1("$uid_shared_with-$source");
			$query->execute(array($uid_owner, self::PUBLICLINK, $source, $token, $permissions));
			$this->token = $token;
		} else {
			if (OC_Group::groupExists($uid_shared_with)) {
				$gid = $uid_shared_with;
				$uid_shared_with = OC_Group::usersInGroup($gid);
				// Remove the owner from the list of users in the group
				$uid_shared_with = array_diff($uid_shared_with, array($uid_owner));
			} else if (OCP\User::userExists($uid_shared_with)) {
				if(OCP\Config::getAppValue('files_sharing', 'allowSharingWithEveryone', 'no') == 'yes') {
					$gid = null;
					$uid_shared_with = array($uid_shared_with);
				} else {
					$userGroups = OC_Group::getUserGroups($uid_owner);
					// Check if the user is in one of the owner's groups
					foreach ($userGroups as $group) {
						if ($inGroup = OC_Group::inGroup($uid_shared_with, $group)) {
							$gid = null;
							$uid_shared_with = array($uid_shared_with);
							break;
						}
					}
					if (!$inGroup) {
						throw new Exception("You can't share with ".$uid_shared_with);
					}
				}
			} else {
				throw new Exception($uid_shared_with." is not a user");
			}
			foreach ($uid_shared_with as $uid) {
				// Check if this item is already shared with the user
				$checkSource = OCP\DB::prepare("SELECT source FROM *PREFIX*sharing WHERE source = ? AND uid_shared_with ".self::getUsersAndGroups($uid, false));
				$resultCheckSource = $checkSource->execute(array($source))->fetchAll();
				// TODO Check if the source is inside a folder
				if (count($resultCheckSource) > 0) {
					if (!isset($gid)) {
						throw new Exception("This item is already shared with ".$uid);
					} else {
						// Skip this user if sharing with a group
						continue;
					}
				}
				// Check if the target already exists for the user, if it does append a number to the name
				$sharedFolder = '/'.$uid.'/files/Shared';
				$target = $sharedFolder."/".basename($source);
				$checkTarget = OCP\DB::prepare("SELECT source FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with ".self::getUsersAndGroups($uid, false)." LIMIT 1");
				$result = $checkTarget->execute(array($target))->fetchAll();
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
						$target = $name."_".$counter.$ext;
						$result = $checkTarget->execute(array($target))->fetchAll();
						$counter++;
					}
				}
				// Update mtime of shared folder to invoke a file cache rescan
				$rootView=new OC_FilesystemView('/');
				if (!$rootView->is_dir($sharedFolder)) {
					if (!$rootView->is_dir('/'.$uid.'/files')) {
						OC_Util::tearDownFS();
						OC_Util::setupFS($uid);
						OC_Util::tearDownFS();
					}
					$rootView->mkdir($sharedFolder);
				}
				$rootView->touch($sharedFolder);
				if (isset($gid)) {
					$uid = $uid."@".$gid;
				}
				$query->execute(array($uid_owner, $uid, $source, $target, $permissions));
			}
		}
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
	* Generate a string to be used for searching for uid_shared_with that handles both users and groups
	* @param $uid (Optional) The uid to get the user groups for, a gid to get the users in a group, or if not set the current user
	* @return An IN operator as a string
	*/
	private static function getUsersAndGroups($uid = null, $includePrivateLinks = true) {
		$in = " IN(";
		if (isset($uid) && OC_Group::groupExists($uid)) {
			$users = OC_Group::usersInGroup($uid);
			foreach ($users as $user) {
				// Add a comma only if the the current element isn't the last
				if ($user !== end($users)) {
					$in .= "'".$user."@".$uid."', ";
				} else {
					$in .= "'".$user."@".$uid."'";
				}
			}
		} else if (isset($uid)) {
			// TODO Check if this is necessary, only constructor needs it as IN. It would be better for other queries to just return =$uid
			$in .= "'".$uid."'";
			$groups = OC_Group::getUserGroups($uid);
			foreach ($groups as $group) {
				$in .= ", '".$uid."@".$group."'";
			}
		} else {
			$uid = OCP\USER::getUser();
			$in .= "'".$uid."'";
			$groups = OC_Group::getUserGroups($uid);
			foreach ($groups as $group) {
				$in .= ", '".$uid."@".$group."'";
			}
		}
		if ($includePrivateLinks) {
			$in .= ", '".self::PUBLICLINK."'";
		}
		$in .= ")";
		return $in;
	}

	private static function updateFolder($uid_shared_with) {
		if ($uid_shared_with != self::PUBLICLINK) {
			if (OC_Group::groupExists($uid_shared_with)) {
				$uid_shared_with = OC_Group::usersInGroup($uid_shared_with);
				// Remove the owner from the list of users in the group
				$uid_shared_with = array_diff($uid_shared_with, array(OCP\USER::getUser()));
			} else {
				$pos = strrpos($uid_shared_with, '@');
				if ($pos !== false && OC_Group::groupExists(substr($uid_shared_with, $pos + 1))) {
					$uid_shared_with = array(substr($uid_shared_with, 0, $pos));
				} else {
					$uid_shared_with = array($uid_shared_with);
				}
			}
			foreach ($uid_shared_with as $uid) {
				$sharedFolder = $uid.'/files/Shared';
				// Update mtime of shared folder to invoke a file cache rescan
				$rootView = new OC_FilesystemView('/');
				$rootView->touch($sharedFolder);
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
		$query = OCP\DB::prepare("INSERT INTO *PREFIX*sharing VALUES(?,?,?,?,?)");
		$query->execute(array($item[0]['uid_owner'], OCP\USER::getUser(), $source, $newTarget, $item[0]['permissions']));
	}

	/**
	 * Get the item with the specified target location
	 * @param $target The target location of the item
	 * @return An array with the item
	 */
	public static function getItem($target) {
		$target = self::cleanPath($target);
		$query = OCP\DB::prepare("SELECT uid_owner, source, permissions FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with = ? LIMIT 1");
		return $query->execute(array($target, OCP\USER::getUser()))->fetchAll();
	}

	 /**
	 * Get the item with the specified source location
	 * @param $source The source location of the item
	 * @return An array with the users and permissions the item is shared with
	 */
	public static function getMySharedItem($source) {
		$source = self::cleanPath($source);
		$query = OCP\DB::prepare("SELECT uid_shared_with, permissions FROM *PREFIX*sharing WHERE source = ? AND uid_owner = ?");
		$result = $query->execute(array($source, OCP\USER::getUser()))->fetchAll();
		if (count($result) > 0) {
			return $result;
		} else if ($originalSource = self::getSource($source)) {
			return $query->execute(array($originalSource, OCP\USER::getUser()))->fetchAll();
		} else {
			return false;
		}
	}

	/**
	 * Get all items the current user is sharing
	 * @return An array with all items the user is sharing
	 */
	public static function getMySharedItems() {
		$query = OCP\DB::prepare("SELECT uid_shared_with, source, permissions FROM *PREFIX*sharing WHERE uid_owner = ?");
		return $query->execute(array(OCP\USER::getUser()))->fetchAll();
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
		$query = OCP\DB::prepare("SELECT uid_owner, source, target, permissions FROM *PREFIX*sharing WHERE SUBSTR(source, 1, ?) = ? OR SUBSTR(target, 1, ?) = ? AND uid_shared_with ".self::getUsersAndGroups());
		return $query->execute(array($length, $folder, $length, $folder))->fetchAll();
	}

	/**
	 * Get the source and target parent folders of the specified target location
	 * @param $target The target location of the item
	 * @return An array with the keys 'source' and 'target' with the values of the source and target parent folders
	 */
	public static function getParentFolders($target) {
		$target = self::cleanPath($target);
		$query = OCP\DB::prepare("SELECT source FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with".self::getUsersAndGroups()." LIMIT 1");
		// Prevent searching for user directory e.g. '/MTGap/files'
		$userDirectory = substr($target, 0, strpos($target, "files") + 5);
		$target = dirname($target);
		$result = array();
		while ($target != "" && $target != "/" && $target != "." && $target != $userDirectory && $target != "\\") {
			// Check if the parent directory of this target location is shared
			$result = $query->execute(array($target))->fetchAll();
			if (count($result) > 0) {
				break;
			}
			$target = dirname($target);
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
		$query = OCP\DB::prepare("SELECT source FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with ".self::getUsersAndGroups()." LIMIT 1");
		$result = $query->execute(array($target))->fetchAll();
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

	public static function getTarget($source) {
		$source = self::cleanPath($source);
		$query = OCP\DB::prepare("SELECT target FROM *PREFIX*sharing WHERE source = ? AND uid_owner = ? LIMIT 1");
		$result = $query->execute(array($source, OCP\USER::getUser()))->fetchAll();
		if (count($result) > 0) {
			return $result[0]['target'];
		} else {
			// TODO Check in folders
			return false;
		}
	}

	/**
	 * Get the user's permissions for the item at the specified target location
	 * @param $target The target location of the item
	 * @return The permissions, use bitwise operators to check against the constants WRITE and DELETE
	 */
	public static function getPermissions($target) {
		$target = self::cleanPath($target);
		$query = OCP\DB::prepare("SELECT permissions FROM *PREFIX*sharing WHERE target = ? AND uid_shared_with ".self::getUsersAndGroups()." LIMIT 1");
		$result = $query->execute(array($target))->fetchAll();
		if (count($result) > 0) {
			return $result[0]['permissions'];
		} else {
			$folders = self::getParentFolders($target);
			if ($folders == true) {
				$result = $query->execute(array($folders['target']))->fetchAll();
				if (count($result) > 0) {
					return $result[0]['permissions'];
				}
			} else {
				OCP\Util::writeLog('files_sharing',"Not existing parent folder : ".$target,OCP\Util::ERROR);
				return false;
			}
		}
	}

	/**
	 * Get the token for a public link
	 * @return The token of the public link, a sha1 hash
	 */
	public function getToken() {
		return $this->token;
	}

	/**
	 * Get the token for a public link
	 * @param $source The source location of the item
	 * @return The token of the public link, a sha1 hash
	 */
	public static function getTokenFromSource($source) {
		$query = OCP\DB::prepare("SELECT target FROM *PREFIX*sharing WHERE source = ? AND uid_shared_with = ? AND uid_owner = ? LIMIT 1");
		$result = $query->execute(array($source, self::PUBLICLINK, OCP\USER::getUser()))->fetchAll();
		if (count($result) > 0) {
			return $result[0]['target'];
		} else {
			return false;
		}
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
		$query = OCP\DB::prepare("UPDATE *PREFIX*sharing SET target = REPLACE(target, ?, ?) WHERE uid_shared_with ".self::getUsersAndGroups());
		$query->execute(array($oldTarget, $newTarget));
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
		$query = OCP\DB::prepare("UPDATE *PREFIX*sharing SET permissions = ? WHERE SUBSTR(source, 1, ?) = ? AND uid_owner = ? AND uid_shared_with ".self::getUsersAndGroups($uid_shared_with));
		$query->execute(array($permissions, strlen($source), $source, OCP\USER::getUser()));
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
		$uid_owner = OCP\USER::getUser();
		$query = OCP\DB::prepare("DELETE FROM *PREFIX*sharing WHERE SUBSTR(source, 1, ?) = ? AND uid_owner = ? AND uid_shared_with ".self::getUsersAndGroups($uid_shared_with, false));
		$query->execute(array(strlen($source), $source, $uid_owner));
		self::updateFolder($uid_shared_with);
	}

	/**
	* Unshare the item from the current user, removes it only from the database and doesn't touch the source file
	*
	* You must use the pullOutOfFolder() function before you call unshareFromMySelf() and set the delete parameter to false to unshare from self a file inside a shared folder
	*
	* @param $target The target location of the item
	* @param $delete (Optional) If true delete the entry from the database, if false the permission is set to UNSHARED
	*/
	public static function unshareFromMySelf($target, $delete = true) {
		$target = self::cleanPath($target);
		if ($delete) {
			$query = OCP\DB::prepare("DELETE FROM *PREFIX*sharing WHERE SUBSTR(target, 1, ?) = ? AND uid_shared_with ".self::getUsersAndGroups());
			$query->execute(array(strlen($target), $target));
		} else {
			$query = OCP\DB::prepare("UPDATE *PREFIX*sharing SET permissions = ? WHERE SUBSTR(target, 1, ?) = ? AND uid_shared_with ".self::getUsersAndGroups());
			$query->execute(array(self::UNSHARED, strlen($target), $target));
		}
	}

	/**
	* Remove the item from the database, the owner deleted the file
	* @param $arguments Array of arguments passed from OC_Hook
	*/
	public static function deleteItem($arguments) {
		$source = "/".OCP\USER::getUser()."/files".self::cleanPath($arguments['path']);
		$result = self::getMySharedItem($source);
		if (is_array($result)) {
			foreach ($result as $item) {
				self::updateFolder($item['uid_shared_with']);
			}
		}
		$query = OCP\DB::prepare("DELETE FROM *PREFIX*sharing WHERE SUBSTR(source, 1, ?) = ? AND uid_owner = ?");
		$query->execute(array(strlen($source), $source, OCP\USER::getUser()));
	}

	/**
	* Rename the item in the database, the owner renamed the file
	* @param $arguments Array of arguments passed from OC_Hook
	*/
	public static function renameItem($arguments) {
		$oldSource = "/".OCP\USER::getUser()."/files".self::cleanPath($arguments['oldpath']);
		$newSource = "/".OCP\USER::getUser()."/files".self::cleanPath($arguments['newpath']);
		$query = OCP\DB::prepare("UPDATE *PREFIX*sharing SET source = REPLACE(source, ?, ?) WHERE uid_owner = ?");
		$query->execute(array($oldSource, $newSource, OCP\USER::getUser()));
	}

	public static function updateItem($arguments) {
		$source = "/".OCP\USER::getUser()."/files".self::cleanPath($arguments['path']);
		$result = self::getMySharedItem($source);
		if (is_array($result)) {
			foreach ($result as $item) {
				self::updateFolder($item['uid_shared_with']);
			}
		}
	}

	public static function removeUser($arguments) {
		$query = OCP\DB::prepare("SELECT uid_shared_with FROM *PREFIX*sharing WHERE uid_owner = ?");
		$result = $query->execute(array($arguments['uid']))->fetchAll();
		if (is_array($result)) {
			$result = array_unique($result);
			foreach ($result as $item) {
				self::updateFolder($item['uid_shared_with']);
			}
			$query = OCP\DB::prepare('DELETE FROM *PREFIX*sharing WHERE uid_owner = ? OR uid_shared_with '.self::getUsersAndGroups($arguments['uid']));
			$query->execute(array($arguments['uid']));
		}
	}

	public static function addToGroupShare($arguments) {
		$length = -strlen($arguments['gid']) - 1;
		$query = OCP\DB::prepare('SELECT uid_owner, source, permissions FROM *PREFIX*sharing WHERE SUBSTR(uid_shared_with, '.$length.') = ?');
		$gid = '@'.$arguments['gid'];
		$result = $query->execute(array($gid))->fetchAll();
		if (count($result) > 0) {
			$lastSource = '';
			for ($i = 0; $i < count($result) - 1; $i++) {
				if ($result[$i]['source'] != $lastSource) {
					new OC_Share($result[$i]['source'], $arguments['gid'], $result[$i]['permissions']);
					$lastSource = $result[$i]['source'];
				}
			}
		}
	}

	public static function removeFromGroupShare($arguments) {
		$query = OCP\DB::prepare('DELETE FROM *PREFIX*sharing WHERE uid_shared_with = ?');
		$query->execute(array($arguments['uid'].'@'.$arguments['gid']));
		self::updateFolder($arguments['uid']);
	}

}

?>
