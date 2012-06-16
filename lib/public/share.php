<?php

/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
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
*/

namespace OCP;

/**
* This class provides the ability for apps to share their content between users.
* Apps must create a backend class that extends OCP\Share_Backend and register it with this class.
*/
class Share {

	const SHARETYPE_USER = 0;
	const SHARETYPE_GROUP = 1;
	const SHARETYPE_CONTACT = 2;
	const SHARETYPE_PRIVATE_LINK = 3;

	const PERMISSION_READ = 0;
	const PERMISSION_UPDATE = 1;
	const PERMISSION_DELETE = 2;
	const PERMISSION_SHARE = 3;
	
	private static $backendTypes = array();
	private static $backends = array();
	private static $sharedFolder = '/Shared/';

	/**
	* @brief Register a sharing backend class that extends OCP\Share_Backend for an item type
	* @param string Class
	* @param string Item type
	* @param string (optional) Depends on item type
	* @param array (optional) List of supported file extensions if this item type depends on files
	* @return Returns true if backend is registered or false if error
	*/
	public static function registerBackend($class, $itemType, $dependsOn = null, $supportedFileExtensions = null) {
		if (is_subclass_of($class, 'OCP\Share_Backend')) {
			if (!isset(self::$backendTypes[$itemType])) {
				self::$backendTypes[$itemType] = array('class' => $class, 'dependsOn' => $dependsOn, 'supportedFileExtensions' => $supportedFileExtensions);
				return true;
			} else {
				\OC_Log::write('OCP\Share', 'Sharing backend '.$class.' not registered, '.self::$backendTypes[$itemType]['class'].' is already registered for '.$itemType, \OC_Log::WARN);
				return false;
			}
		}
		\OC_Log::write('OCP\Share', 'Sharing backend '.$class.' not registered, the class must extend abstract class OC_Share_Backend', \OC_Log::ERROR);
		return false;
	}

	/**
	* @brief Get the items of item type shared with the current user
	* @param string Item type
	* @return
	*/
	public static function getItemsSharedWith($itemType) {
		return self::getItems($itemType, null, \OC_User::getUser(), true, null, true);
	}

	/**
	* @brief Get the item of item type shared with the current user
	* @param string Item type
	* @return
	*/
	public static function getItemSharedWith($itemType, $item) {
		return self::getItems($itemType, $item, \OC_User::getUser(), true, null, true, 1);
	}

	/**
	* @brief Get the shared items of item type owned by the current user
	* @param string Item type
	* @return The
	*/
	public static function getItemsOwned($itemType) {
		return self::getItems($itemType, null, null, null, \OC_User::getUser(), true);
	}

	/**
	* @brief Get the shared item of item type owned by the current user
	* @param string Item type
	* @return The
	*/
	public static function getItemOwned($itemType, $item) {
		return self::getItems($itemType, $item, null, null, \OC_User::getUser(), true, 1);
	}

	/**
	* @brief Share an item with a user, group, or via private link
	* @param string Item type
	* @param string Item
	* @param int SHARETYPE_USER | SHARETYPE_GROUP | SHARETYPE_PRIVATE_LINK
	* @param string User or group the item is being shared with
	* @param string
	* @return Returns true on success or false on failure
	*/
	public static function share($itemType, $item, $shareType, $shareWith, $permissions) {
		$uidOwner = \OC_User::getUser();
		// Verify share type and sharing conditions are met
		switch ($shareType) {
			case self::SHARETYPE_USER:
				if ($shareWith == $uidOwner) {
					\OC_Log::write('OCP\Share', 'Sharing '.$item.' failed, because the user '.$shareWith.' is the item owner', \OC_Log::ERROR);
					return false;
				}
				if (!\OC_User::userExists($shareWith)) {
					\OC_Log::write('OCP\Share', 'Sharing '.$item.' failed, because the user '.$shareWith.' does not exist', \OC_Log::ERROR);
					return false;
				} else {
					$inGroup = array_intersect(\OC_Group::getUserGroups($uidOwner), \OC_Group::getUserGroups($shareWith));
					if (empty($inGroup)) {
						\OC_Log::write('OCP\Share', 'Sharing '.$item.' failed, because the user '.$shareWith.' is not a member of any groups that '.$uidOwner.' is a member of', \OC_Log::ERROR);
						return false;
					}
				}
				if (self::getItems($itemType, $item, $shareWith, true, $uidOwner, false, 1)) {
					\OC_Log::write('OCP\Share', 'Sharing '.$item.' failed, because this item is already shared with the user '.$shareWith, \OC_Log::ERROR);
					return false;
				}
				$uidSharedWith = array($shareWith);
				$gidSharedWith = null;
				break;
			case self::SHARETYPE_GROUP:
				if (!\OC_Group::groupExists($shareWith)) {
					\OC_Log::write('OCP\Share', 'Sharing '.$item.' failed, because the group '.$shareWith.' does not exist', \OC_Log::ERROR);
					return false;
				} else if (!\OC_Group::inGroup($uidOwner, $shareWith)) {
					\OC_Log::write('OCP\Share', 'Sharing '.$item.' failed, because '.$uidOwner.' is not a member of the group '.$shareWith, \OC_Log::ERROR);
					return false;
				}
				if (self::getItems($itemType, $item, null, $shareWith, $uidOwner, false, 1)) {
					\OC_Log::write('OCP\Share', 'Sharing '.$item.' failed, because this item is already shared with the group '.$shareWith, \OC_Log::ERROR);
					return false;
				}
				$uidSharedWith = array_diff(\OC_Group::usersInGroup($shareWith), array($uidOwner));
				$gidSharedWith = $shareWith;
				break;
			case self::SHARETYPE_PRIVATE_LINK:
				// TODO don't loop through folder conversion
				$uidSharedWith = '';
				$gidSharedWith = null;
			default:
				\OC_Log::write('OCP\Share', 'Share type '.$shareType.' is not valid for '.$item, \OC_Log::ERROR);
				return false;
		}
		// If the item is a folder, scan through the folder looking for equivalent item types
		if ($itemType == 'folder') {
			$parentFolder = self::put('folder', $item, $uidSharedWith, $gidSharedWith, $uidOwner, $permissions, true);
			if ($parentFolder && $files = \OC_Files::getDirectoryContent($item)) {
				for ($i = 0; $i < count($files); $i++) {
					$name = substr($files[$i]['name'], strpos($files[$i]['name'], $item) - strlen($item));
					if ($files[$i]['mimetype'] == 'httpd/unix-directory' && $children = OC_Files::getDirectoryContent($name, '/')) {
						// Continue scanning into child folders
						array_push($files, $children);
					} else {
						// Pass on to put() to check if this item should be converted, the item won't be inserted into the database unless it can be converted
						self::put('file', $name, $uidSharedWith, $gidSharedWith, $uidOwner, $permissions, $parentFolder);
					}
				}
				return $return;
			}
			return false;
		} else {
			// Put the item into the database
			return self::put($itemType, $item, $uidSharedWith, $gidSharedWith, $uidOwner, $permissions);
		}
	}

	/**
	* @brief Unshare an item from a user, group, or delete a private link
	* @param string Item type
	* @param string Item
	* @param int SHARETYPE_USER | SHARETYPE_GROUP | SHARETYPE_PRIVATE_LINK
	* @param string User or group the item is being shared with
	* @return Returns true on success or false on failure
	*/
	public static function unshare($itemType, $item, $shareType, $shareWith) {
		$uidOwner = \OC_User::getUser();
		switch ($shareType) {
			case self::SHARETYPE_USER:
			case self::SHARETYPE_PRIVATE_LINK:
				$item = self::getItems($itemType, $item, $shareWith, null, $uidOwner, false, 1);
				break;
			case self::SHARETYPE_GROUP:
				$item = self::getItems($itemType, $item, null, $shareWith, $uidOwner, false, 1);
				break;
			default:
				\OC_Log::write('OCP\Share', 'Share type '.$shareType.' is not valid for item '.$item, \OC_Log::ERROR);
				return false;
		}
		if ($item) {
			self::delete($item['item_source'], $item['id']);
			return true;
		}
		return false;
	}

	/**
	* @brief
	* @param
	* @param
	* @return
	*/
	public static function unshareFromSelf($itemType, $itemTarget) {
		$uidSharedWith = \OC_User::getUser();
		if ($item = self::getItems($itemType, $itemTarget, $uidSharedWith, true, null, false, 1)) {
			// TODO Check if item is inside a shared folder and was converted
			if ($item['parent']) {
				$query = \OC_DB::prepare('SELECT item_type FROM *PREFIX*sharing WHERE id = ? LIMIT 1');
				$result = $query->execute(array($item['parent']))->fetchRow();
				// TODO Check other parents
				if (isset($result['item_type']) && $result['item_type'] = 'folder') {
					return false;
				}
			}
			// Check if this is a group share, if it is a group share a new entry needs to be created marked as unshared from self
			if ($item['uid_shared_with'] == null) {
				$query = \OC_DB::prepare('INSERT INTO *PREFIX*sharing VALUES(?,?,?,?,?,?,?,?,?,?)');
				$result = $query->execute(array($item['item_type'], $item['item_source'], $item['item_target'], $uidSharedWith, $item['gid_shared_with'], $item['uid_owner'], self::UNSHARED_FROM_SELF, $item['stime'], $item['file_source'], $item['file_target']));
				if (\OC_DB::isError($result)) {
// 					\OC_Log::write('OCP\Share', 'Share type '.$shareType.' is not valid for item '.$item, \OC_Log::ERROR);
					return false;
				}
			}
			return self::delete($item['item_source'], $item['id'], true);
		}
		return false;
	}

	/**
	* @brief Set the target name of the item for the current user
	* @param string Item type
	* @param string Old item name
	* @param string New item name
	* @return Returns true on success or false on failure
	*/
	public static function setTarget($itemType, $oldTarget, $newTarget) {
		if ($backend = self::getBackend($itemType)) {
			$uidSharedWith = \OC_User::getUser();
			if ($item = self::getItems($itemType, $oldTarget, $uidSharedWith, true, null, false, 1)) {
				// Check if this is a group share
				if ($item['uid_shared_with'] == null) {
					// A new entry needs to be created exclusively for the user
					$query = \OC_DB::prepare('INSERT INTO *PREFIX*sharing VALUES(?,?,?,?,?,?,?,?,?,?)');
					if (isset($item['file_target'])) {
						$fileTarget = $newTarget;
					} else {
						$fileTarget = null;
					}
					$query->execute(array($itemType, $item['item_source'], $newTarget, $uidSharedWith, $item['gid_shared_with'], $item['uid_owner'], $item['permissions'], $item['stime'], $item['file_source'], $fileTarget));
					return true;
				} else {
					// Check if this item is a file or folder, update the file_target as well if this is the case
					if ($itemType == 'file' || $itemType == 'folder') {
						$query = \OC_DB::prepare('UPDATE *PREFIX*sharing SET item_target = ?, file_target = REPLACE(file_target, ?, ?) WHERE uid_shared_with = ?');
						$query->execute(array($newTarget, $oldTarget, $newTarget, $uidSharedWith));
					} else {
						$query = \OC_DB::prepare('UPDATE *PREFIX*sharing SET item_target = ? WHERE item_type = ? AND item_target = ? AND uid_shared_with = ?');
						$query->execute(array($newTarget, $itemType, $oldTarget, $uidSharedWith));
					}
					return true;
				}
			}
		}
		return false;
	}

	/**
	* @brief Set the permissions of an item for a specific user or group
	* @param string Item type
	* @param string Item
	* @param int SHARETYPE_USER | SHARETYPE_GROUP | SHARETYPE_PRIVATE_LINK
	* @param string User or group the item is being shared with
	* @param
	* @return Returns true on success or false on failure
	*/
	public static function setPermissions($itemType, $item, $shareType, $shareWith, $permissions) {
		$uidOwner = \OC_User::getUser();
		switch ($shareType) {
			case self::SHARETYPE_USER:
			case self::SHARETYPE_PRIVATE_LINK:
				$item = self::getItems($itemType, $item, $shareWith, null, $uidOwner, false, 1);
				break;
			case self::SHARETYPE_GROUP:
				$item = self::getItems($itemType, $item, null, $shareWith, $uidOwner, false, 1);
				break;
			default:
				\OC_Log::write('OCP\Share', 'Share type '.$shareType.' is not valid for item '.$item, \OC_Log::ERROR);
				return false;
		}
		if ($item) {
			// Check if this item is a reshare and verify that the permissions granted don't exceed the parent shared item
			if (isset($item['parent'])) {
				$query = \OC_DB::prepare('SELECT permissions FROM *PREFIX*sharing WHERE id = ? LIMIT 1');
				$result = $query->execute(array($item['parent']))->fetchRow();
				if (!isset($result['permissions']) || $permissions > $result['permissions']) {
					\OC_Log::write('OCP\Share', '', \OC_Log::ERROR);
					return false;
				}
			}
			$query = \OC_DB::prepare('UPDATE *PREFIX*sharing SET permissions = ? WHERE id = ?');
			$query->execute(array($permissions, $item['id']));
			// Check if permissions were reduced
			if ($permissions < $item['permissions']) {
				// Reduce the permissions for all reshares of this item
				$ids = array($item['id']);
				$query = \OC_DB::prepare('SELECT id, parent, permissions FROM *PREFIX*sharing WHERE item_source = ?');
				$result = $query->execute(array($item['item_source']));
				while ($item = $result->fetchRow()) {
					if (in_array($item['parent'], $ids) && $item['permissions'] > $permissions) {
						$ids[] = $item['id'];
					}
				}
				// Remove parent item from array, this item's permissions already got updated
				unset($ids[0]);
				if (!empty($ids)) {
					$query = \OC_DB::prepare('UPDATE *PREFIX*sharing SET permissions = ? WHERE id IN (?)');
					$query->execute(array($permissions, implode(',', $ids)));
				}
			}
			return true;
		}
		return false;
	}

		/**
	* @brief Get the backend class for the specified item type
	* @param string Item type
	* @return Sharing backend object
	*/
	private static function getBackend($itemType) {
		if (isset(self::$backends[$itemType])) {
			return self::$backends[$itemType];
		} else if (isset(self::$backendTypes[$itemType]['class'])) {
			$class = self::$backendTypes[$itemType]['class'];
			if (class_exists($class)) {
				self::$backends[$itemType] = new $class;
				return self::$backends[$itemType];
			} else {
				\OC_Log::write('OCP\Share', 'Sharing backend '.$class.' not found', \OC_Log::ERROR);
				return false;
			}
		}
		\OC_Log::write('OCP\Share', 'Sharing backend for '.$itemType.' not found', \OC_Log::ERROR);
		return false;
	}

	/**
	* @brief Get a list of parent item types for the specified item type
	* @param string Item type
	* @return array
	*/
	private static function getParentItemTypes($itemType) {
		$parents = array($itemType);
		foreach (self::$backendTypes as $type => $backend) {
			if (in_array($backend->dependsOn, $parents)) {
				$parents[] = $type;
			}
		}
		if (!empty($parents)) {
			return $parents;
		}
		return false;
	}

	/**
	* @brief Get shared items from the database
	* @param string Item type
	* @param string Item (optional)
	* @param string User the item(s) is(are) shared with
	* @param string|bool Group the item(s) is(are) shared with
	* @param string User that is the owner of shared items (optional)
	* @param bool Translate the items back into their original source (optional)
	* @param int Number of items to return, -1 to return all matches (optional)
	*
	* See public functions getItem(s)... for parameter usage
	*
	*/
	private static function getItems($itemType, $item = null, $uidSharedWith = null, $gidSharedWith = null, $uidOwner = null, $translate = true, $limit = -1) {
		if ($backend = self::getBackend($itemType)) {
			// Check if there are any parent types that include this type of items, e.g. a music album contains songs
			if (isset($itemType)) {
				if ($parents = self::getParentItemTypes($itemType)) {
				$where = "WHERE item_type IN ('".$itemType."'";
				foreach ($parents as $parent) {
					$where .= ", '.$parent.'";
				}
				$where .= ')';
				} else {
					$where = "WHERE item_type = '".$itemType."'";
				}
				// TODO exclude items that are inside of folders and got converted i.e. songs, pictures
				if ($itemType == 'files') {

				}
			}
			if (isset($uidSharedWith)) {
				if ($gidSharedWith === true) {
					$where .= " AND (uid_shared_with = '".$uidSharedWith."'";
					// Include group shares
					$groups = \OC_Group::getUserGroups($uidSharedWith);
					if (!empty($groups)) {
						$groups = "'".implode("','", $groups)."'";
						$where .= " OR gid_shared_with IN (".$groups.") AND (uid_shared_with IS NULL OR uid_shared_with = '".$uidSharedWith."'))";
					}
				} else {
					$where .= " AND uid_shared_with = '".$uidSharedWith."'";
				}
			} else if (isset($gidSharedWith)) {
				$where .= " AND gid_shared_with = '".$gidSharedWith."' AND uid_shared_with IS NULL";
			}
			if (isset($uidOwner)) {
				$where .= " AND uid_owner = '".$uidOwner."'";
			}
			if (isset($item)) {
				// If looking for own shared items, check item_source else check item_target
				if (isset($uidOwner)) {
					// Check if this item depends on a file and getSource() returned an array
					$source = $backend->getSource($item, $uidOwner);
					if (is_array($source)) {
						$itemSource = $source['item'];
					} else {
						$itemSource = $source;
					}
					$where .= " AND item_source = '".$itemSource."'";
				} else {
					$where .= " AND item_target = '".$item."'";
				}
			}
			if ($limit != -1) {
				$where .= ' LIMIT '.$limit;
			}
			echo $where.'<br />';
			$query = \OC_DB::prepare('SELECT * FROM *PREFIX*sharing '.$where);
			$result = $query->execute();
			$items = array();
			while ($item = $result->fetchRow()) {
				// Check if this is part of a group share and the user has a different target from the group share
				if ($gidSharedWith === true && isset($item['gid_shared_with']) && isset($item['uid_shared_with'])) {
					// Remove the default group share item from the array

				}
				if ($translate) {
					if ($item['item_type'] != $itemType && $parentBackend = self::getBackend($item['item_type'])) {
						if ($itemType == 'files') {
							// TODO Don't get children, rather get file sources
						}
						// TODO add to array parent name
						$children = $parentBackend->getChildren($item);
						foreach ($children as $child) {
							$items[] = $child;
						}
					} else {
						$items[] = $backend->translateItem($item);
					}
				}
			}
			if (!empty($items)) {
				return $items;
			}
		}
		return false;
	}

	/**
	* @brief Put shared item into the database
	* @param string Item type
	* @param string Item
	* @param array User(s) the item is being shared with
	* @param string|null Group the item is being shared with
	* @param string Owner of the item
	* @param string
	* @param int Parent folder target (optional)
	* @return bool
	*/
	private static function put($itemType, $item, $uidSharedWith, $gidSharedWith, $uidOwner, $permissions, $parentFolder = null) {
		// Check file extension for an equivalent item type to convert to
		if ($itemType == 'file') {
			$extension = strtolower(substr($item, strrpos($item, '.') + 1));
			foreach (self::$backendTypes as $type => $backend) {
				if (isset($backend['dependsOn']) && $backend['dependsOn'] == 'file' && isset($backend['supportedFileExtensions']) && in_array($extension, $backend['supportedFileExtensions'])) {
					$itemType = $type;
					break;
				}
			}
			// Exit if this is being called for a file inside a folder, and no equivalent item type is found
			if (isset($parentFolder) && $itemType == 'file') {
				return false;
			}
		}
		if ($backend = self::getBackend($itemType)) {
			// Check if this is a reshare
			if ($checkReshare = self::getItemSharedWith($itemType, $item)) {
				// TODO Check if resharing is allowed
				$parent = $checkReshare['id'];
				$itemSource = $checkReshare['item_source'];
				$fileSource = $checkReshare['file_source'];
				$fileTarget = $checkReshare['file_target'];
			} else {
				$parent = null;
				$source = $backend->getSource($item, $uidOwner);
				if (!$source) {
					\OC_Log::write('OCP\Share', 'Sharing '.$item.' failed, because the sharing backend for '.$itemType.' could not find its source', \OC_Log::ERROR);
					return false;
				} else if (is_array($source)) {
					$itemSource = $source['item'];
					$fileSource = \OC_FileCache::getId($source['file']);
				} else {
					$itemSource = $source;
					$fileSource = null;
				}
			}
			$query = \OC_DB::prepare('INSERT INTO *PREFIX*sharing (item_type, item_source, item_target, parent, uid_shared_with, gid_shared_with, uid_owner, permissions, stime, file_source, file_target) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
			// If the item is being shared with a group, add an entry for the group itself
			if (isset($gidSharedWith)) {
				if (isset($fileSource)) {
					if (isset($parentFolder)) {
						$groupFileTarget = self::getBackend('file')->generateTarget($source['file'], false);
					} else {
						$groupFileTarget = self::getBackend('file')->generateTarget($source['file'], false);
					}
				} else {
					$groupFileTarget = null;
				}
				$groupItemTarget = $backend->generateTarget($item, false);
				$query->execute(array($itemType, $itemSource, $groupItemTarget, $parent, null, $gidSharedWith, $uidOwner, $permissions, time(), $fileSource, $groupFileTarget));
				// Save this id, any extra rows for this group share will need to reference it
				$parent = \OC_DB::insertid('*PREFIX*sharing');
			}
			// Need to loop through the users in a group, just in case we need to change the item target or file target for a specific user
			// A normal user share also goes through this loop
			foreach ($uidSharedWith as $uid) {
				$itemTarget = $backend->generateTarget($item, $uid);
				if (isset($fileSource)) {
					if (isset($parentFolder)) {
						// TODO
					} else {
						$fileTarget = self::getBackend('file')->generateTarget($source['file'], $uid);
					}
				} else {
					$fileTarget = null;
				}
				if (isset($gidSharedWith)) {
					// Insert an extra row for the group share if the item or file target is unique for this user
					if ($itemTarget != $groupItemTarget || (isset($fileSource) && $fileTarget != $groupFileTarget)) {
						$query->execute(array($itemType, $itemSource, $itemTarget, $parent, $uid, $gidSharedWith, $uidOwner, $permissions, time(), $fileSource, $fileTarget));
						\OC_DB::insertid('*PREFIX*sharing');
					}
				} else {
					$query->execute(array($itemType, $itemSource, $itemTarget, $parent, $uid, $gidSharedWith, $uidOwner, $permissions, time(), $fileSource, $fileTarget));
					\OC_DB::insertid('*PREFIX*sharing');
				}
				
			}
			return true;
		}
		return false;
	}

	/**
	* @brief Delete all reshares of an item
	* @param string
	* @param int Id of item to delete
	* @param bool
	*/
	private static function delete($itemSource, $parent, $excludeParent = false) {
		$query = \OC_DB::prepare('SELECT id, parent FROM *PREFIX*sharing WHERE item_source = ?');
		$result = $query->execute(array($itemSource));
		$ids = array($parent);
		while ($item = $result->fetchRow()) {
			if (in_array($item['parent'], $ids)) {
				$ids[] = $item['id'];
			}
		}
		if ($excludeParent) {
			unset($ids[0]);
		}
		if (!empty($ids)) {
			$query = \OC_DB::prepare('DELETE FROM *PREFIX*sharing WHERE id IN (?)');
			$query->execute(array(implode("','", $ids)));
		}
	}

	/**
	* Hook Listeners
	*/
	
	public static function post_writeFile($arguments) {
		// TODO
	}

	public static function post_deleteUser($arguments) {
		// Delete any items shared with the deleted user
		$query = \OC_DB::prepare('DELETE FROM *PREFIX*sharing WHERE uid_shared_with = ?');
		$result = $query->execute(array($arguments['uid']));
		// Delete any items the deleted user shared
		$query = \OC_DB::prepare('SELECT id, item_source FROM *PREFIX*sharing WHERE uid_owner = ?');
		$result = $query->execute(array($arguments['uid']));
		while ($item = $result->fetchRow()) {
			self::delete($item['item_source'], $item['id']);
		}
	}

	public static function post_addToGroup($arguments) {
		// TODO
	}

	public static function post_removeFromGroup($arguments) {
		// TODO
	}

}

/**
* Abstract backend class that apps must extend to share content.
*/
abstract class Share_Backend {

	public static $dependsOn;
	public static $supportedFileExtensions = array();

	/**
	* @brief Get the source of the item to be stored in the database
	* @param string Item
	* @param string Owner of the item
	* @return mixed|array|false Source
	*
	* Return a 
	* Return an array if the item is file dependent, the array needs two keys: 'item' and 'file'
	* Return false if the item does not exist for the user
	*
	* The translateItem() function will translate the source returned back into the item
	*/
	public abstract function getSource($item, $uid);

	/**
	* @brief Get a unique name of the item for the specified user
	* @param string Item
	* @param string|false User the item is being shared with
	* @param array|null List of similar item names already existing as shared items
	* @return string Target name
	*
	* This function needs to verify that the user does not already have an item with this name.
	* If it does generate a new name e.g. name_#
	*/
	public abstract function generateTarget($item, $uid, $exclude = null);

	public abstract function transteItem($source);

}

abstract class Share_Backend_Parent extends Share_Backend {

	public abstract function getChildren($item);
    
}

?>