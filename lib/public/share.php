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

	
	const SHARE_TYPE_USER = 0;
	const SHARE_TYPE_GROUP = 1;
	const SHARE_TYPE_PRIVATE_LINK = 3;

	const PERMISSION_READ = 0;
	const PERMISSION_UPDATE = 1;
	const PERMISSION_DELETE = 2;
	const PERMISSION_SHARE = 3;

	const FORMAT_NONE = -1;
	const FORMAT_STATUSES = -2;

	private static $shareTypeUserAndGroups = -1;
	private static $shareTypeGroupUserUnique = 2;
	private static $backends = array();
	private static $backendTypes = array();

	/**
	* @brief Register a sharing backend class that extends OCP\Share_Backend for an item type
	* @param string Item type
	* @param string Backend class
	* @param string (optional) Depends on item type
	* @param array (optional) List of supported file extensions if this item type depends on files
	* @return Returns true if backend is registered or false if error
	*/
	public static function registerBackend($itemType, $class, $dependsOn = null, $supportedFileExtensions = null) {
		if (!isset(self::$backendTypes[$itemType])) {
			self::$backendTypes[$itemType] = array('class' => $class, 'dependsOn' => $dependsOn, 'supportedFileExtensions' => $supportedFileExtensions);
			return true;
		}
		\OC_Log::write('OCP\Share', 'Sharing backend '.$class.' not registered, '.self::$backendTypes[$itemType]['class'].' is already registered for '.$itemType, \OC_Log::WARN);
		return false;
	}

	/**
	* @brief Get the items of item type shared with the current user
	* @param string Item type
	* @param int Format (optional) Format type must be defined by the backend
	* @param int Number of items to return (optional) Returns all by default
	* @return Return depends on format
	*/
	public static function getItemsSharedWith($itemType, $format = self::FORMAT_NONE, $limit = -1) {
		return self::getItems($itemType, null, self::$shareTypeUserAndGroups, \OC_User::getUser(), null, $format, $limit);
	}

	/**
	* @brief Get the item of item type shared with the current user
	* @param string Item type
	* @param string Item target
	* @param int Format (optional) Format type must be defined by the backend
	* @return Return depends on format
	*/
	public static function getItemSharedWith($itemType, $itemTarget, $format = self::FORMAT_NONE) {
		return self::getItems($itemType, $itemTarget, self::$shareTypeUserAndGroups, \OC_User::getUser(), null, $format, 1);
	}

	/**
	* @brief Get the shared items of item type owned by the current user
	* @param string Item type
	* @param int Format (optional) Format type must be defined by the backend
	* @param int Number of items to return (optional) Returns all by default
	* @return Return depends on format
	*/
	public static function getItemsShared($itemType, $format = self::FORMAT_NONE, $limit = -1) {
		return self::getItems($itemType, null, null, null, \OC_User::getUser(), $format, $limit);
	}

	/**
	* @brief Get the shared item of item type owned by the current user
	* @param string Item type
	* @param string Item
	* @param int Format (optional) Format type must be defined by the backend
	* @return Return depends on format
	*/
	public static function getItemShared($itemType, $item, $format = self::FORMAT_NONE) {
		return self::getItems($itemType, $item, null, null, \OC_User::getUser(), $format);
	}

	/**
	* @brief Get the status of each shared item of item type owned by the current user
	* @param string Item type
	* @param int Number of items to return (optional) Returns all by default
	* @return array, item as key with a value of true if item has a private link or false
	*/
	public static function getItemsSharedStatuses($itemType, $limit = -1) {
		return self::getItems($itemType, null, null, null, \OC_User::getUser(), self::FORMAT_STATUSES, $limit);
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
			case self::SHARE_TYPE_USER:
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
				if ($checkShareExists = self::getItems($itemType, $item, self::SHARE_TYPE_USER, $shareWith, $uidOwner, self::FORMAT_NONE, 1) && !empty($checkShareExists)) {
					\OC_Log::write('OCP\Share', 'Sharing '.$item.' failed, because this item is already shared with the user '.$shareWith, \OC_Log::ERROR);
					return false;
				}
				break;
			case self::SHARE_TYPE_GROUP:
				if (!\OC_Group::groupExists($shareWith)) {
					\OC_Log::write('OCP\Share', 'Sharing '.$item.' failed, because the group '.$shareWith.' does not exist', \OC_Log::ERROR);
					return false;
				} else if (!\OC_Group::inGroup($uidOwner, $shareWith)) {
					\OC_Log::write('OCP\Share', 'Sharing '.$item.' failed, because '.$uidOwner.' is not a member of the group '.$shareWith, \OC_Log::ERROR);
					return false;
				}
				if ($checkShareExists = self::getItems($itemType, $item, self::SHARE_TYPE_GROUP, $shareWith, $uidOwner, self::FORMAT_NONE, 1) && !empty($checkShareExists)) {
					\OC_Log::write('OCP\Share', 'Sharing '.$item.' failed, because this item is already shared with the group '.$shareWith, \OC_Log::ERROR);
					return false;
				}
				// Convert share with into an array with the keys group and users
				$group = $shareWith;
				$shareWith = array();
				$shareWith['group'] = $group;
				$shareWith['users'] = array_diff(\OC_Group::usersInGroup($group), array($uidOwner));
				break;
			case self::SHARETYPE_PRIVATE_LINK:
				// TODO don't loop through folder conversion
				$uidSharedWith = '';
				$gidSharedWith = null;
				break;
			// Future share types need to include their own conditions
			default:
				\OC_Log::write('OCP\Share', 'Share type '.$shareType.' is not valid for '.$item, \OC_Log::ERROR);
				return false;
		}
		// If the item is a folder, scan through the folder looking for equivalent item types
		if ($itemType == 'folder') {
			$parentFolder = self::put('folder', $item, $shareType, $shareWith, $uidOwner, $permissions, true);
			if ($parentFolder && $files = \OC_Files::getDirectoryContent($item)) {
				for ($i = 0; $i < count($files); $i++) {
					$name = substr($files[$i]['name'], strpos($files[$i]['name'], $item) - strlen($item));
					if ($files[$i]['mimetype'] == 'httpd/unix-directory' && $children = OC_Files::getDirectoryContent($name, '/')) {
						// Continue scanning into child folders
						array_push($files, $children);
					} else {
						// Pass on to put() to check if this item should be converted, the item won't be inserted into the database unless it can be converted
						self::put('file', $name, $shareType, $shareWith, $uidOwner, $permissions, $parentFolder);
					}
				}
				return $return;
			}
			return false;
		} else {
			// Put the item into the database
			return self::put($itemType, $item, $shareType, $shareWith, $uidOwner, $permissions);
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
		if ($item = self::getItems($itemType, $item, $shareType, $shareWith, \OC_User::getUser(), self::FORMAT_NONE, 1) && !empty($item)) {
			self::delete($item['id']);
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
			// Check if item is inside a shared folder and was converted
			if ($item['parent']) {
				$query = \OC_DB::prepare('SELECT item_type FROM *PREFIX*share WHERE id = ? LIMIT 1');
				$result = $query->execute(array($item['parent']))->fetchRow();
				if (isset($result['item_type']) && $result['item_type'] == 'folder') {
					return false;
				}
			}
			// Check if this is a group share, if it is a group share a new entry needs to be created marked as unshared from self
			if ($item['uid_shared_with'] == null) {
				$query = \OC_DB::prepare('INSERT INTO *PREFIX*share VALUES(?,?,?,?,?,?,?,?,?,?)');
				$result = $query->execute(array($item['item_type'], $item['item_source'], $item['item_target'], $uidSharedWith, $item['gid_shared_with'], $item['uid_owner'], self::UNSHARED_FROM_SELF, $item['stime'], $item['file_source'], $item['file_target']));
				if (\OC_DB::isError($result)) {
// 					\OC_Log::write('OCP\Share', 'Share type '.$shareType.' is not valid for item '.$item, \OC_Log::ERROR);
					return false;
				}
			}
			return self::delete($item['id'], true);
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
			// TODO Check permissions for setting target?
			if ($item = self::getItems($itemType, $oldTarget, self::SHARE_TYPE_USER, $uidSharedWith, null, self::FORMAT_NONE, 1) && !empty($item)) {
				// Check if this is a group share
				if ($item['uid_shared_with'] == null) {
					// A new entry needs to be created exclusively for the user
					$query = \OC_DB::prepare('INSERT INTO *PREFIX*share VALUES(?,?,?,?,?,?,?,?,?,?)');
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
						$query = \OC_DB::prepare('UPDATE *PREFIX*share SET item_target = ?, file_target = REPLACE(file_target, ?, ?) WHERE uid_shared_with = ?');
						$query->execute(array($newTarget, $oldTarget, $newTarget, $uidSharedWith));
					} else {
						$query = \OC_DB::prepare('UPDATE *PREFIX*share SET item_target = ? WHERE item_type = ? AND item_target = ? AND uid_shared_with = ?');
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
		if ($item = self::getItems($itemType, $item, $shareType, $shareWith, \OC_User::getUser(), self::FORMAT_NONE, 1) && !empty($item)) {
			// Check if this item is a reshare and verify that the permissions granted don't exceed the parent shared item
			if (isset($item['parent'])) {
				$query = \OC_DB::prepare('SELECT permissions FROM *PREFIX*share WHERE id = ? LIMIT 1');
				$result = $query->execute(array($item['parent']))->fetchRow();
				if (!isset($result['permissions']) || $permissions > $result['permissions']) {
					\OC_Log::write('OCP\Share', '', \OC_Log::ERROR);
					return false;
				}
			}
			$query = \OC_DB::prepare('UPDATE *PREFIX*share SET permissions = ? WHERE id = ?');
			$query->execute(array($permissions, $item['id']));
			// Check if permissions were reduced
			if ($permissions < $item['permissions']) {
				// Reduce the permissions for all reshares of this item
				$ids = array($item['id']);
				$query = \OC_DB::prepare('SELECT id, parent, permissions FROM *PREFIX*share WHERE item_source = ?');
				$result = $query->execute(array($item['item_source']));
				while ($item = $result->fetchRow()) {
					if (in_array($item['parent'], $ids) && $item['permissions'] > $permissions) {
						$ids[] = $item['id'];
					}
				}
				// Remove parent item from array, this item's permissions already got updated
				unset($ids[0]);
				if (!empty($ids)) {
					$query = \OC_DB::prepare('UPDATE *PREFIX*share SET permissions = ? WHERE id IN (?)');
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
				if (!is_subclass_of(self::$backends[$itemType], 'OCP\Share_Backend')) {
					\OC_Log::write('OCP\Share', 'Sharing backend '.$class.' must extend abstract class OC_Share_Backend', \OC_Log::ERROR);
					return false;
				}
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
		foreach (self::$backends as $type => $backend) {
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
	* @param string Item or item target (optional)
	* @param string User the item(s) is(are) shared with
	* @param string|bool Group the item(s) is(are) shared with
	* @param string User that is the owner of shared items (optional)
	* @param bool Translate the items back into their original source (optional)
	* @param int Number of items to return, -1 to return all matches (optional)
	*
	* See public functions getItem(s)... for parameter usage
	*
	*/
	private static function getItems($itemType, $item = null, $shareType = null, $shareWith = null, $uidOwner = null, $format = self::FORMAT_NONE, $limit = -1) {
		if ($backend = self::getBackend($itemType)) {
			// Check if there are any parent types that include this type of items, e.g. a music album contains songs
			if (isset($itemType)) {
				if ($parents = self::getParentItemTypes($itemType)) {
					$where = "WHERE item_type IN ('".implode("','", $parents)."')";
				} else {
					$where = "WHERE item_type = '".$itemType."'";
				}
				// TODO exclude items that are inside of folders and got converted i.e. songs, pictures
				if ($itemType == 'files') {

				}
			}
			if (isset($shareType) && isset($shareWith)) {
				// Include all user and group items
				if ($shareType == self::$shareTypeUserAndGroups) {
					$where .= " AND share_type IN (".self::SHARE_TYPE_USER.",".self::SHARE_TYPE_GROUP.",".self::$shareTypeGroupUserUnique.")";
					$groups = \OC_Group::getUserGroups($shareWith);
					$userAndGroups = array_merge(array($shareWith), $groups);
					$where .= " AND share_with IN ('".implode("','", $userAndGroups)."')";
				} else {
					$where .= " AND share_type = ".$shareType." AND share_with = '".$shareWith."'";
				}
			}
			if (isset($uidOwner)) {
				$where .= " AND uid_owner = '".$uidOwner."'";
				if (!isset($shareType)) {
					// Prevent unique user targets for group shares from being selected
					$where .= " AND share_type != '".self::$shareTypeGroupUserUnique."'";
				}
			}
			if (isset($item)) {
				// If looking for own shared items, check item_source else check item_target
				if (isset($uidOwner)) {
					$source = $backend->getSource($item, $uidOwner);
					// If item type is a file, file source needs to be checked in case the item was converted
					if ($itemType == 'file') {
						$where .= " AND file_source = ".\OC_FileCache::getId($source['file']);
					} else {
						// Check if this item depends on a file and getSource() returned an array
						if (is_array($source)) {
							$itemSource = $source['item'];
						} else {
							$itemSource = $source;
						}
						$where .= " AND item_source = '".$itemSource."'";
					}
				} else {
					if ($itemType == 'file' && substr($item, -1) == '/') {
						// Special case to select only the shared files inside the folder
						$where .= " AND file_target LIKE '".$item."%/'";
					} else {
						$where .= " AND item_target = '".$item."'";
					}
				}
			}
			if ($limit != -1) {
				if ($limit == 1 && $shareType == self::$shareTypeUserAndGroups) {
					// Make sure the unique user target is returned if it exists, unique targets should follow the group share in the database
					// If the limit is not 1, the filtering can be done later
					$where .= ' ORDER BY id DESC';
				}
				$where .= ' LIMIT '.$limit;
			}
			$query = \OC_DB::prepare('SELECT * FROM *PREFIX*share '.$where);
			$result = $query->execute();
			$items = array();
			while ($item = $result->fetchRow()) {
				// Filter out duplicate group shares for users with unique targets
				if ($item['share_type'] == self::$shareTypeGroupUserUnique) {
					// Group shares should already be in the items array
					unset($items[$item['parent']]);
				}
				// TODO Add in parent item types children?
				if ($parents && in_array($item['item_type'], $parents)) {
					$children[] = $item;
				}
				$items[$item['id']] = $item;
			}
			if (!empty($items)) {
				if ($format == self::FORMAT_NONE) {
					if ($limit == 1) {
						// Return just the item instead of 2-dimensional array
						return $items[key($items)];
					}
					return $items;
				} else if ($format == self::FORMAT_STATUSES) {
					$statuses = array();
					foreach ($items as $item) {
						if ($item['share_type'] == self::SHARE_TYPE_PRIVATE_LINK) {
							$statuses[$item['item']] = true;
						} else if (!isset($statuses[$item['item']])) {
							$statuses[$item['item']] = false;
						}
					}
					return $statuses;
				} else {
					return $backend->formatItems($items, $format);
				}
			}
		}
		return array();
	}

	/**
	* @brief Put shared item into the database
	* @param string Item type
	* @param string Item
	* @param string|array User(s) the item is being shared with
	* @param string|null Group the item is being shared with
	* @param string Owner of the item
	* @param string
	* @param bool|array Parent folder target (optional)
	* @return bool
	*/
	private static function put($itemType, $item, $shareType, $shareWith, $uidOwner, $permissions, $parentFolder = null) {
		// Check file extension for an equivalent item type to convert to
		if ($itemType == 'file') {
			$extension = strtolower(substr($item, strrpos($item, '.') + 1));
			foreach (self::$backends as $type => $backend) {
				if (isset($backend->dependsOn) && $backend->dependsOn == 'file' && isset($backend->supportedFileExtensions) && in_array($extension, $backend->supportedFileExtensions)) {
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
			if ($checkReshare = self::getItemSharedWith($itemType, $item) && !empty($checkReshare)) {
				// TODO Check if resharing is allowed
				// TODO Don't check if inside folder
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
			$query = \OC_DB::prepare('INSERT INTO *PREFIX*share (item_type, item, item_source, item_target, parent, share_type, share_with, uid_owner, permissions, stime, file_source, file_target) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
			// Share with a group
			if ($shareType == self::SHARE_TYPE_GROUP) {
				if (isset($fileSource)) {
					if ($parentFolder) {
						if ($parentFolder === true) {
							$groupFileTarget = self::getBackend('file')->generateTarget($source['file'], false);
							// Set group default file target for future use
							$parentFolders[0]['folder'] = $groupFileTarget;
						} else {
							// Get group default file target
							$groupFileTarget = $parentFolder[0]['folder'].$item;
							$parent = $parentFolder[0]['id'];
							unset($parentFolder[0]);
							// Only loop through users we know have different file target paths
							$uidSharedWith = array_keys($parentFolder);
						}
					} else {
						$groupFileTarget = self::getBackend('file')->generateTarget($source['file'], false);
					}
				} else {
					$groupFileTarget = null;
				}
				$groupItemTarget = $backend->generateTarget($item, false);
				$query->execute(array($itemType, $item, $itemSource, $groupItemTarget, $parent, $shareType, $shareWith['group'], $uidOwner, $permissions, time(), $fileSource, $groupFileTarget));
				// Save this id, any extra rows for this group share will need to reference it
				$parent = \OC_DB::insertid('*PREFIX*share');
				// Loop through all users of this group in case we need to add an extra row
				foreach ($shareWith['users'] as $uid) {
					$itemTarget = $backend->generateTarget($item, $uid);
					if (isset($fileSource)) {
						if ($parentFolder) {
							if ($parentFolder === true) {
								$fileTarget = self::getBackend('file')->generateTarget($source['file'], $uid);
								if ($fileTarget != $groupFileTarget) {
									$parentFolders[$uid]['folder'] = $fileTarget;
								}
							} else if (isset($parentFolder[$uid])) {
								$fileTarget = $parentFolder[$uid]['folder'].$item;
								$parent = $parentFolder[$uid]['id'];
							}
						} else {
							$fileTarget = self::getBackend('file')->generateTarget($source['file'], $uid);
						}
					} else {
						$fileTarget = null;
					}
					// Insert an extra row for the group share if the item or file target is unique for this user
					if ($itemTarget != $groupItemTarget || (isset($fileSource) && $fileTarget != $groupFileTarget)) {
						$query->execute(array($itemType, $item, $itemSource, $itemTarget, $parent, self::$shareTypeGroupUserUnique, $uid, $uidOwner, $permissions, time(), $fileSource, $fileTarget));
						$id = \OC_DB::insertid('*PREFIX*share');
					}
					if ($parentFolder === true) {
						$parentFolders['id'] = $id;
					}
				}
				if ($parentFolder === true) {
					// Return parent folders to preserve file target paths for potential children
					return $parentFolders;
				}
			} else {
				$itemTarget = $backend->generateTarget($item, $shareWith);
				if (isset($fileSource)) {
					if ($parentFolder) {
						if ($parentFolder === true) {
							$fileTarget = self::getBackend('file')->generateTarget($source['file'], $shareWith);
							$parentFolders['folder'] = $fileTarget;
						} else {
							$fileTarget = $parentFolder['folder'].$item;
							$parent = $parentFolder['id'];
						}
					} else {
						$fileTarget = self::getBackend('file')->generateTarget($source['file'], $shareWith);
					}
				} else {
					$fileTarget = null;
				}
				$query->execute(array($itemType, $item, $itemSource, $itemTarget, $parent, $shareType, $shareWith, $uidOwner, $permissions, time(), $fileSource, $fileTarget));
				$id = \OC_DB::insertid('*PREFIX*share');
				if ($parentFolder === true) {
					$parentFolders['id'] = $id;
					// Return parent folder to preserve file target paths for potential children
					return $parentFolders;
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
	private static function delete($parent, $excludeParent = false) {
		$query = \OC_DB::prepare('SELECT id FROM *PREFIX*share WHERE parent IN (?)');
		$ids = array($parent);
		while ($item = $query->execute(array(implode("','", $ids)))->fetchRow()) {
			$ids[] = $item['id'];
		}
		if ($excludeParent) {
			unset($ids[0]);
		}
		if (!empty($ids)) {
			$query = \OC_DB::prepare('DELETE FROM *PREFIX*share WHERE id IN (?)');
			$query->execute(array(implode("','", $ids)));
		}
	}

	/**
	* Hook Listeners
	*/
	
	public static function post_deleteUser($arguments) {
		// Delete any items shared with the deleted user
		$query = \OC_DB::prepare('DELETE FROM *PREFIX*share WHERE uid_shared_with = ?');
		$result = $query->execute(array($arguments['uid']));
		// Delete any items the deleted user shared
		$query = \OC_DB::prepare('SELECT id, item_source FROM *PREFIX*share WHERE uid_owner = ?');
		$result = $query->execute(array($arguments['uid']));
		while ($item = $result->fetchRow()) {
			self::delete($item['id']);
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

	public $dependsOn;
	public $supportedFileExtensions = array();

	/**
	* @brief Get the source of the item to be stored in the database
	* @param string Item
	* @param string Owner of the item
	* @return mixed|array|false Source
	*
	* Return an array if the item is file dependent, the array needs two keys: 'item' and 'file'
	* Return false if the item does not exist for the user
	*
	* The formatItems() function will translate the source returned back into the item
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



	/**
	* @brief Converts the shared item sources back into the item in the specified format
	* @param array Sources of shared items
	* @param int Format 
	* @return ?
	* 
	* The items array is formatted with the sources as the keys to an array with the following keys: item_target, permissions, stime
	* This function allows the backend to control the output of shared items with custom formats.
	* It is only called through calls to the public getItem(s)SharedWith functions.
	*/
	public abstract function formatItems($items, $format);


}

abstract class Share_Backend_Parent extends Share_Backend {

	public abstract function getChildren($item);
    
}

?>
