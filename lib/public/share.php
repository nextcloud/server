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

\OC_Hook::connect('OC_User', 'post_deleteUser', 'OCP\Share', 'post_deleteUser');
\OC_Hook::connect('OC_User', 'post_addToGroup', 'OCP\Share', 'post_addToGroup');
\OC_Hook::connect('OC_User', 'post_removeFromGroup', 'OCP\Share', 'post_removeFromGroup');

/**
* This class provides the ability for apps to share their content between users.
* Apps must create a backend class that implements OCP\Share_Backend and register it with this class.
*/
class Share {

	const SHARE_TYPE_USER = 0;
	const SHARE_TYPE_GROUP = 1;
	const SHARE_TYPE_PRIVATE_LINK = 3;
	const SHARE_TYPE_EMAIL = 4;
	const SHARE_TYPE_CONTACT = 5;
	const SHARE_TYPE_REMOTE = 6;

	/** CRUDS permissions (Create, Read, Update, Delete, Share) using a bitmask
	* Construct permissions for share() and setPermissions with Or (|) e.g. Give user read and update permissions: PERMISSION_READ | PERMISSION_UPDATE
	* Check if permission is granted with And (&) e.g. Check if delete is granted: if ($permissions & PERMISSION_DELETE)
	* Remove permissions with And (&) and Not (~) e.g. Remove the update permission: $permissions &= ~PERMISSION_UPDATE
	* Apps are required to handle permissions on their own, this class only stores and manages the permissions of shares
	*/
	const PERMISSION_CREATE = 4;
	const PERMISSION_READ = 1;
	const PERMISSION_UPDATE = 2;
	const PERMISSION_DELETE = 8;
	const PERMISSION_SHARE = 16;

	const FORMAT_NONE = -1;
	const FORMAT_STATUSES = -2;
	const FORMAT_SOURCES = -3;

	private static $shareTypeUserAndGroups = -1;
	private static $shareTypeGroupUserUnique = 2;
	private static $backends = array();
	private static $backendTypes = array();

	/**
	* @brief Register a sharing backend class that implements OCP\Share_Backend for an item type
	* @param string Item type
	* @param string Backend class
	* @param string (optional) Depends on item type
	* @param array (optional) List of supported file extensions if this item type depends on files
	* @return Returns true if backend is registered or false if error
	*/
	public static function registerBackend($itemType, $class, $collectionOf = null, $supportedFileExtensions = null) {
		if (!isset(self::$backendTypes[$itemType])) {
			self::$backendTypes[$itemType] = array('class' => $class, 'collectionOf' => $collectionOf, 'supportedFileExtensions' => $supportedFileExtensions);
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
	public static function getItemsSharedWith($itemType, $format = self::FORMAT_NONE, $parameters = null, $limit = -1, $includeCollections = false) {
		return self::getItems($itemType, null, self::$shareTypeUserAndGroups, \OC_User::getUser(), null, $format, $parameters, $limit, $includeCollections);
	}

	/**
	* @brief Get the item of item type shared with the current user
	* @param string Item type
	* @param string Item target
	* @param int Format (optional) Format type must be defined by the backend
	* @return Return depends on format
	*/
	public static function getItemSharedWith($itemType, $itemTarget, $format = self::FORMAT_NONE, $parameters = null, $includeCollections = false) {
		return self::getItems($itemType, $itemTarget, self::$shareTypeUserAndGroups, \OC_User::getUser(), null, $format, $parameters, 1, $includeCollections);
	}

	/**
	* @brief Get the shared items of item type owned by the current user
	* @param string Item type
	* @param int Format (optional) Format type must be defined by the backend
	* @param int Number of items to return (optional) Returns all by default
	* @return Return depends on format
	*/
	public static function getItemsShared($itemType, $format = self::FORMAT_NONE, $parameters = null, $limit = -1, $includeCollections = false) {
		return self::getItems($itemType, null, null, null, \OC_User::getUser(), $format, $parameters, $limit, $includeCollections);
	}

	/**
	* @brief Get the shared item of item type owned by the current user
	* @param string Item type
	* @param string Item source
	* @param int Format (optional) Format type must be defined by the backend
	* @return Return depends on format
	*/
	public static function getItemShared($itemType, $itemSource, $format = self::FORMAT_NONE, $parameters = null, $includeCollections = false) {
		return self::getItems($itemType, $itemSource, null, null, \OC_User::getUser(), $format, $parameters, -1, $includeCollections);
	}

	/**
	* @brief Share an item with a user, group, or via private link
	* @param string Item type
	* @param string Item source
	* @param int SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_PRIVATE_LINK
	* @param string User or group the item is being shared with
	* @param int CRUDS permissions
	* @return bool Returns true on success or false on failure
	*/
	public static function share($itemType, $itemSource, $shareType, $shareWith, $permissions) {
		$uidOwner = \OC_User::getUser();
		// Verify share type and sharing conditions are met
		switch ($shareType) {
			case self::SHARE_TYPE_USER:
				if ($shareWith == $uidOwner) {
					$message = 'Sharing '.$itemSource.' failed, because the user '.$shareWith.' is the itemSource owner';
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				}
				if (!\OC_User::userExists($shareWith)) {
					$message = 'Sharing '.$itemSource.' failed, because the user '.$shareWith.' does not exist';
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				} else {
					$inGroup = array_intersect(\OC_Group::getUserGroups($uidOwner), \OC_Group::getUserGroups($shareWith));
					if (empty($inGroup)) {
						$message = 'Sharing '.$itemSource.' failed, because the user '.$shareWith.' is not a member of any groups that '.$uidOwner.' is a member of';
						\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
						throw new \Exception($message);
					}
				}
				break;
			case self::SHARE_TYPE_GROUP:
				if (!\OC_Group::groupExists($shareWith)) {
					$message = 'Sharing '.$itemSource.' failed, because the group '.$shareWith.' does not exist';
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				} else if (!\OC_Group::inGroup($uidOwner, $shareWith)) {
					$message = 'Sharing '.$itemSource.' failed, because '.$uidOwner.' is not a member of the group '.$shareWith;
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				}
				// Convert share with into an array with the keys group and users
				$group = $shareWith;
				$shareWith = array();
				$shareWith['group'] = $group;
				$shareWith['users'] = array_diff(\OC_Group::usersInGroup($group), array($uidOwner));
				break;
			case self::SHARE_TYPE_PRIVATE_LINK:
				$shareWith = md5(uniqid($itemSource, true));
				return self::put($itemType, $itemSource, $shareType, $shareWith, $uidOwner, $permissions);
			case self::SHARE_TYPE_CONTACT:
				if (!\OC_App::isEnabled('contacts')) {
					$message = 'Sharing '.$itemSource.' failed, because the contacts app is not enabled';
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					return false;
				}
				$vcard = \OC_Contacts_App::getContactVCard($shareWith);
				if (!isset($vcard)) {
					$message = 'Sharing '.$itemSource.' failed, because the contact does not exist';
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				}
				$details = \OC_Contacts_VCard::structureContact($vcard);
				// TODO Add ownCloud user to contacts vcard
				if (!isset($details['EMAIL'])) {
					$message = 'Sharing '.$itemSource.' failed, because no email address is associated with the contact';
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				}
				return self::share($itemType, $itemSource, self::SHARE_TYPE_EMAIL, $details['EMAIL'], $permissions);
				break;
			// Future share types need to include their own conditions
			default:
				\OC_Log::write('OCP\Share', 'Share type '.$shareType.' is not valid for '.$itemSource, \OC_Log::ERROR);
				return false;
		}
		// TODO This query has pretty bad performance if there are large collections, figure out a way to make the collection searching more efficient
		if (self::getItems($itemType, $itemSource, $shareType, $shareWith, $uidOwner, self::FORMAT_NONE, null, 1, true)) {
			$message = 'Sharing '.$itemSource.' failed, because this itemSource is already shared with '.$shareWith;
			\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
			throw new \Exception($message);
		}
		// If the item is a folder, scan through the folder looking for equivalent item types
		if ($itemType == 'folder') {
			$parentFolder = self::put('folder', $itemSource, $shareType, $shareWith, $uidOwner, $permissions, true);
			if ($parentFolder && $files = \OC_Files::getDirectoryContent($itemSource)) {
				for ($i = 0; $i < count($files); $i++) {
					$name = substr($files[$i]['name'], strpos($files[$i]['name'], $itemSource) - strlen($itemSource));
					if ($files[$i]['mimetype'] == 'httpd/unix-directory' && $children = \OC_Files::getDirectoryContent($name, '/')) {
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
			return self::put($itemType, $itemSource, $shareType, $shareWith, $uidOwner, $permissions);
		}
	}

	/**
	* @brief Unshare an item from a user, group, or delete a private link
	* @param string Item type
	* @param string Item source
	* @param int SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_PRIVATE_LINK
	* @param string User or group the item is being shared with
	* @return Returns true on success or false on failure
	*/
	public static function unshare($itemType, $itemSource, $shareType, $shareWith) {
		if ($item = self::getItems($itemType, $itemSource, $shareType, $shareWith, \OC_User::getUser(), self::FORMAT_NONE, null, 1)) {
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
		$backend = self::getBackend($itemType);
		$uidSharedWith = \OC_User::getUser();
		// TODO Check permissions for setting target?
		if ($item = self::getItems($itemType, $oldTarget, self::SHARE_TYPE_USER, $uidSharedWith, null, self::FORMAT_NONE, null, 1, false)) {
			// TODO Fix
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

	/**
	* @brief Set the permissions of an item for a specific user or group
	* @param string Item type
	* @param string Item source
	* @param int SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_PRIVATE_LINK
	* @param string User or group the item is being shared with
	* @param int CRUDS permissions
	* @return Returns true on success or false on failure
	*/
	public static function setPermissions($itemType, $itemSource, $shareType, $shareWith, $permissions) {
		if ($item = self::getItems($itemType, $itemSource, $shareType, $shareWith, \OC_User::getUser(), self::FORMAT_NONE, null, 1, false)) {
			// Check if this item is a reshare and verify that the permissions granted don't exceed the parent shared item
			if (isset($item['parent'])) {
				$query = \OC_DB::prepare('SELECT permissions FROM *PREFIX*share WHERE id = ? LIMIT 1');
				$result = $query->execute(array($item['parent']))->fetchRow();
				if (~(int)$result['permissions'] & $permissions) {
					\OC_Log::write('OCP\Share', 'Setting permissions for '.$itemSource.' failed, because the permissions exceed permissions granted to the parent item', \OC_Log::ERROR);
					return false;
				}
			}
			$query = \OC_DB::prepare('UPDATE *PREFIX*share SET permissions = ? WHERE id = ?');
			$query->execute(array($permissions, $item['id']));
			// Check if permissions were removed
			if ($item['permissions'] & ~$permissions) {
				// If share permission is removed all reshares must be deleted
				if (($item['permissions'] & self::PERMISSION_SHARE) && (~$permissions & self::PERMISSION_SHARE)) {
					self::delete($item['id'], true);
				} else {
					$ids = array();
					$parents = array($item['id']);
					while (!empty($parents)) {
						$parents = "'".implode("','", $parents)."'";
						$query = \OC_DB::prepare('SELECT id, permissions FROM *PREFIX*share WHERE parent IN ('.$parents.')');
						$result = $query->execute();
						// Reset parents array, only go through loop again if items are found that need permissions removed
						$parents = array();
						while ($item = $result->fetchRow()) {
							// Check if permissions need to be removed
							if ($item['permissions'] & ~$permissions) {
								// Add to list of items that need permissions removed
								$ids[] = $item['id'];
								$parents[] = $item['id'];
							}
						}
					}
					// Remove the permissions for all reshares of this item
					if (!empty($ids)) {
						$ids = "'".implode("','", $ids)."'";
						$query = \OC_DB::prepare('UPDATE *PREFIX*share SET permissions = permissions & ? WHERE id IN ('.$ids.')');
						$query->execute(array($permissions));
					}
				}
			}
			return true;
		}
		\OC_Log::write('OCP\Share', 'Setting permissions for '.$itemSource.' failed, because the item was not found', \OC_Log::ERROR);
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
				if (!(self::$backends[$itemType] instanceof Share_Backend)) {
					$message = 'Sharing backend '.$class.' must implement the interface OCP\Share_Backend';
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				}
				return self::$backends[$itemType];
			} else {
				$message = 'Sharing backend '.$class.' not found';
				\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
				throw new \Exception($message);
			}
		}
		$message = 'Sharing backend for '.$itemType.' not found';
		\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
		throw new \Exception($message);
	}

	/**
	* @brief Get a list of collection item types for the specified item type
	* @param string Item type
	* @return array
	*/
	private static function getCollectionItemTypes($itemType) {
		$collectionTypes = array($itemType);
		foreach (self::$backendTypes as $type => $backend) {
			if (in_array($backend['collectionOf'], $collectionTypes)) {
				$collectionTypes[] = $type;
			}
		}
		if (count($collectionTypes) > 1) {
			unset($collectionTypes[0]);
			return $collectionTypes;
		}
		return false;
	}

	/**
	* @brief Get shared items from the database
	* @param string Item type
	* @param string Item source or target (optional)
	* @param int SHARE_TYPE_USER, SHARE_TYPE_GROUP, SHARE_TYPE_PRIVATE_LINK, $shareTypeUserAndGroups, or $shareTypeGroupUserUnique
	* @param string User or group the item is being shared with
	* @param string User that is the owner of shared items (optional)
	* @param int Format to convert items to with formatItems()
	* @param mixed Parameters to pass to formatItems()
	* @param int Number of items to return, -1 to return all matches (optional)
	* @param bool Include collection item types (optional)
	* @return mixed
	*
	* See public functions getItem(s)... for parameter usage
	*
	*/
	private static function getItems($itemType, $item = null, $shareType = null, $shareWith = null, $uidOwner = null, $format = self::FORMAT_NONE, $parameters = null, $limit = -1, $includeCollections = false) {
		$backend = self::getBackend($itemType);
		// Get filesystem root to add it to the file target and remove from the file source
		$root = \OC_Filesystem::getRoot();
		// If includeCollections is true, find collections of this item type, e.g. a music album contains songs
		if ($includeCollections && !isset($item) && $collectionTypes = self::getCollectionItemTypes($itemType)) {
			$where = "WHERE item_type IN ('".implode("','", array_merge(array($itemType), $collectionTypes))."')";
		} else {
			$where = "WHERE item_type = '".$itemType."'";
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
			if ($itemType == 'file' || $itemType == 'folder') {
				$where = "INNER JOIN *PREFIX*fscache ON file_source = *PREFIX*fscache.id ".$where;
				$column = 'file_source';
			} else {
				$column = 'item_source';
			}
		} else {
			if ($itemType == 'file' || $itemType == 'folder') {
				$column = 'file_target';
			} else {
				$column = 'item_target';
			}
		}
		if (isset($item)) {
			// If looking for own shared items, check item_source else check item_target
			if (isset($uidOwner)) {
				// If item type is a file, file source needs to be checked in case the item was converted
				if ($itemType == 'file' || $itemType == 'folder') {
					$where .= " AND path = '".$root.$item."'";
				} else {
					$where .= " AND item_source = '".$item."'";
				}
			} else {
				if ($itemType == 'file' || $itemType == 'folder') {
					$where .= " AND file_target = '".$item."'";
				} else {
					$where .= " AND item_target = '".$item."'";
				}
			}
			if ($includeCollections && $collectionTypes = self::getCollectionItemTypes($itemType)) {
				$where .= " OR item_type IN ('".implode("','", $collectionTypes)."')";
			}
		}
		if ($limit != -1 && !$includeCollections) {
			if ($shareType == self::$shareTypeUserAndGroups) {
				// Make sure the unique user target is returned if it exists, unique targets should follow the group share in the database
				// If the limit is not 1, the filtering can be done later
				$where .= ' ORDER BY *PREFIX*share.id DESC';
			}
			$where .= ' LIMIT '.$limit;
		}
		if ($format == self::FORMAT_STATUSES) {
			if ($itemType == 'file' || $itemType == 'folder') {
				$select = '*PREFIX*share.id, item_type, *PREFIX*share.parent, share_type, *PREFIX*fscache.path as file_source';
			} else {
				$select = 'id, item_type, item_source, parent, share_type';
			}
		} else {
			if (isset($uidOwner)) {
				if ($itemType == 'file' || $itemType == 'folder') {
					$select = '*PREFIX*share.id, item_type, *PREFIX*fscache.path as file_source, *PREFIX*share.parent, share_type, share_with, permissions, stime';
				} else {
					$select = 'id, item_type, item_source, parent, share_type, share_with, permissions, stime, file_source';
				}
			} else {
				$select = '*';
			}
		}
		$root = strlen($root);
		$query = \OC_DB::prepare('SELECT '.$select.' FROM *PREFIX*share '.$where);
		$result = $query->execute();
		$items = array();
		while ($row = $result->fetchRow()) {
			// Remove root from file source paths
			if (isset($uidOwner) && isset($row['file_source'])) {
				$row['file_source'] = substr($row['file_source'], $root);
			}
			// Return only the item instead of a 2-dimensional array
			if ($limit == 1 && $row['item_type'] == $itemType && $row[$column] == $item) {
				if ($format == self::FORMAT_NONE) {
					return $row;
				} else {
					$items[$row['id']] = $row;
					break;
				}
			}
			// Filter out duplicate group shares for users with unique targets
			if ($row['share_type'] == self::$shareTypeGroupUserUnique) {
				// Remove the parent group share
				unset($items[$row['parent']]);
			}
			// TODO Check this outside of the loop
			// Check if this is a collection of the requested item type
			if ($row['item_type'] != $itemType) {
				if ($collectionBackend = self::getBackend($row['item_type'])) {
					$row['collection'] = array('item_type' => $itemType, $column => $row[$column]);
					// Fetch all of the children sources
					$children = $collectionBackend->getChildren($row[$column]);
					foreach ($children as $child) {
						$row['item_source'] = $child;
// 							$row['item_target'] = $child['target']; TODO
						if (isset($item)) {
							if ($row[$column] == $item) {
								// Return only the item instead of a 2-dimensional array
								if ($limit == 1 && $format == self::FORMAT_NONE) {
									return $row;
								} else {
									// Unset the items array and break out of both loops
									$items = array();
									$items[] = $row;
									break 2;
								}
							}
						} else {
							$items[] = $row;
						}
					}
				}
			} else {
				$items[$row['id']] = $row;
			}
		}
		if (!empty($items)) {
			if ($format == self::FORMAT_NONE) {
				return $items;
			} else if ($format == self::FORMAT_STATUSES) {
				$statuses = array();
				foreach ($items as $item) {
					if ($item['share_type'] == self::SHARE_TYPE_PRIVATE_LINK) {
						$statuses[$item[$column]] = true;
					} else if (!isset($statuses[$item[$column]])) {
						$statuses[$item[$column]] = false;
					}
				}
				return $statuses;
			} else {
				return $backend->formatItems($items, $format, $parameters);
			}
		} else if ($limit == 1 || (isset($uidOwner) && isset($item))) {
			return false;
		}
		return array();
	}

	/**
	* @brief Put shared item into the database
	* @param string Item type
	* @param string Item source
	* @param int SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_PRIVATE_LINK
	* @param string User or group the item is being shared with
	* @param int CRUDS permissions
	* @param bool|array Parent folder target (optional)
	* @return bool Returns true on success or false on failure
	*/
	private static function put($itemType, $itemSource, $shareType, $shareWith, $uidOwner, $permissions, $parentFolder = null) {
		// Check file extension for an equivalent item type to convert to
		if ($itemType == 'file') {
			$extension = strtolower(substr($itemSource, strrpos($itemSource, '.') + 1));
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
		$backend = self::getBackend($itemType);
		// Check if this is a reshare
		// TODO This query has pretty bad performance if there are large collections, figure out a way to make the collection searching more efficient
		if ($checkReshare = self::getItemSharedWith($itemType, $itemSource, self::FORMAT_NONE, null, true)) {
			if ($checkReshare['permissions'] & self::PERMISSION_SHARE) {
				// TODO Don't check if inside folder
				$parent = $checkReshare['id'];
				$itemSource = $checkReshare['item_source'];
				$fileSource = $checkReshare['file_source'];
				$fileTarget = $checkReshare['file_target'];
			} else {
				\OC_Log::write('OCP\Share', 'Sharing '.$itemSource.' failed, because resharing is not allowed', \OC_Log::ERROR);
				return false;
			}
		} else {
			$parent = null;
			if (!$backend->isValidSource($itemSource, $uidOwner)) {
				\OC_Log::write('OCP\Share', 'Sharing '.$itemSource.' failed, because the sharing backend for '.$itemType.' could not find its source', \OC_Log::ERROR);
				return false;
			}
			$parent = null;
			if ($backend instanceof Share_Backend_File_Dependent) {
				// NOTE Apps should start using the file cache ids in their tables
				$filePath = $backend->getFilePath($itemSource, $uidOwner);
				$fileSource = \OC_FileCache::getId($filePath);
				if ($fileSource == -1) {
					\OC_Log::write('OCP\Share', 'Sharing '.$itemSource.' failed, because the file could not be found in the file cache', \OC_Log::ERROR);
					return false;
				}
			} else {
				$fileSource = null;
			}
		}
		$query = \OC_DB::prepare('INSERT INTO *PREFIX*share (item_type, item_source, item_target, parent, share_type, share_with, uid_owner, permissions, stime, file_source, file_target) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
		// Share with a group
		if ($shareType == self::SHARE_TYPE_GROUP) {
			if (isset($fileSource)) {
				if ($parentFolder) {
					if ($parentFolder === true) {
						$groupFileTarget = self::generateTarget('file', $filePath, $shareType, $shareWith);
						// Set group default file target for future use
						$parentFolders[0]['folder'] = $groupFileTarget;
					} else {
						// Get group default file target
						$groupFileTarget = $parentFolder[0]['folder'].$itemSource;
						$parent = $parentFolder[0]['id'];
						unset($parentFolder[0]);
						// Only loop through users we know have different file target paths
						$uidSharedWith = array_keys($parentFolder);
					}
				} else {
					$groupFileTarget = self::generateTarget('file', $filePath, $shareType, $shareWith);
				}
			} else {
				$groupFileTarget = null;
			}
			$groupItemTarget = self::generateTarget($itemType, $itemSource, $shareType, $shareWith);
			$query->execute(array($itemType, $itemSource, $groupItemTarget, $parent, $shareType, $shareWith['group'], $uidOwner, $permissions, time(), $fileSource, $groupFileTarget));
			// Save this id, any extra rows for this group share will need to reference it
			$parent = \OC_DB::insertid('*PREFIX*share');
			// Loop through all users of this group in case we need to add an extra row
			foreach ($shareWith['users'] as $uid) {
				$itemTarget = self::generateTarget($itemType, $itemSource, self::SHARE_TYPE_USER, $uid);
				if (isset($fileSource)) {
					if ($parentFolder) {
						if ($parentFolder === true) {
							$fileTarget = self::generateTarget('file', $filePath, self::SHARE_TYPE_USER, $uid);
							if ($fileTarget != $groupFileTarget) {
								$parentFolders[$uid]['folder'] = $fileTarget;
							}
						} else if (isset($parentFolder[$uid])) {
							$fileTarget = $parentFolder[$uid]['folder'].$itemSource;
							$parent = $parentFolder[$uid]['id'];
						}
					} else {
						$fileTarget = self::generateTarget('file', $filePath, self::SHARE_TYPE_USER, $uid);
					}
				} else {
					$fileTarget = null;
				}
				// Insert an extra row for the group share if the item or file target is unique for this user
				if ($itemTarget != $groupItemTarget || (isset($fileSource) && $fileTarget != $groupFileTarget)) {
					$query->execute(array($itemType, $itemSource, $itemTarget, $parent, self::$shareTypeGroupUserUnique, $uid, $uidOwner, $permissions, time(), $fileSource, $fileTarget));
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
			$itemTarget = self::generateTarget($itemType, $itemSource, $shareType, $shareWith);
			if (isset($fileSource)) {
				if ($parentFolder) {
					if ($parentFolder === true) {
						$fileTarget = self::generateTarget('file', $filePath, $shareType, $shareWith);
						$parentFolders['folder'] = $fileTarget;
					} else {
						$fileTarget = $parentFolder['folder'].$itemSource;
						$parent = $parentFolder['id'];
					}
				} else {
					$fileTarget = self::generateTarget('file', $filePath, $shareType, $shareWith);
				}
			} else {
				$fileTarget = null;
			}
			$query->execute(array($itemType, $itemSource, $itemTarget, $parent, $shareType, $shareWith, $uidOwner, $permissions, time(), $fileSource, $fileTarget));
			$id = \OC_DB::insertid('*PREFIX*share');
			if ($parentFolder === true) {
				$parentFolders['id'] = $id;
				// Return parent folder to preserve file target paths for potential children
				return $parentFolders;
			}
		}
		return true;
	}

	/**
	* @brief Generate a unique target for the item
	* @param string Item type
	* @param string Item source
	* @param int SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_PRIVATE_LINK
	* @param string User or group the item is being shared with
	* @return string Item target
	*/
	private static function generateTarget($itemType, $itemSource, $shareType, $shareWith) {
		$backend = self::getBackend($itemType);
		if ($shareType == self::SHARE_TYPE_PRIVATE_LINK) {
			return $backend->generateTarget($itemSource, false);
		} else {
			if ($itemType == 'file' || $itemType == 'folder') {
				$column = 'file_target';
			} else {
				$column = 'item_target';
			}
			$exclude = null;
			// Backend has 3 opportunities to generate a unique target
			for ($i = 0; $i < 2; $i++) {
				if ($shareType == self::SHARE_TYPE_GROUP) {
					$target = $backend->generateTarget($itemSource, false, $exclude);
				} else {
					$target = $backend->generateTarget($itemSource, $shareWith, $exclude);
				}
				if (is_array($exclude) && in_array($target, $exclude)) {
					break;
				}
				// Check if target already exists
				if ($checkTarget = self::getItems($itemType, $target, $shareType, $shareWith, null, self::FORMAT_NONE, null, 1)) {
					// Find similar targets to improve backend's chances to generate a unqiue target
					// TODO query needs to be setup like getItems
					$checkTargets = \OC_DB::prepare('SELECT item_target FROM *PREFIX*share WHERE item_type = ? AND share_with = ? AND '.$column.' LIKE ?');
					$result = $checkTargets->execute(array($itemType, $shareWith, '%'.$target.'%'));
					while ($row = $result->fetchRow()) {
						$exclude[] = $row[$column];
					}
				} else {
					return $target;
				}
			}
		}
		$message = 'Backend did not generate a unique target';
		\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
		throw new \Exception($message);
	}

	/**
	* @brief Delete all reshares of an item
	* @param int Id of item to delete
	* @param bool If true, exclude the parent from the delete (optional)
	* @param string The user that the parent was shared with (optinal)
	*/
	private static function delete($parent, $excludeParent = false, $uidOwner = null) {
		$ids = array($parent);
		$parents = array($parent);
		while (!empty($parents)) {
			$parents = "'".implode("','", $parents)."'";
			// Check the owner on the first search of reshares, useful for finding and deleting the reshares by a single user of a group share
			if (count($ids) == 1 && isset($uidOwner)) {
				$query = \OC_DB::prepare('SELECT id FROM *PREFIX*share WHERE parent IN ('.$parents.') AND uid_owner = ?');
				$result = $query->execute(array($uidOwner));
			} else {
				$query = \OC_DB::prepare('SELECT id FROM *PREFIX*share WHERE parent IN ('.$parents.')');
				$result = $query->execute();
			}
			// Reset parents array, only go through loop again if items are found
			$parents = array();
			while ($item = $result->fetchRow()) {
				$ids[] = $item['id'];
				$parents[] = $item['id'];
			}
		}
		if ($excludeParent) {
			unset($ids[0]);
		}
		if (!empty($ids)) {
			$ids = "'".implode("','", $ids)."'";
			$query = \OC_DB::prepare('DELETE FROM *PREFIX*share WHERE id IN ('.$ids.')');
			$query->execute();
		}
	}

	/**
	* Hook Listeners
	*/
	
	public static function post_deleteUser($arguments) {
		// Delete any items shared with the deleted user
		$query = \OC_DB::prepare('DELETE FROM *PREFIX*share WHERE share_with = ? AND share_type = ? OR share_type = ?');
		$result = $query->execute(array($arguments['uid'], self::SHARE_TYPE_USER, self::$shareTypeGroupUserUnique));
		// Delete any items the deleted user shared
		$query = \OC_DB::prepare('SELECT id FROM *PREFIX*share WHERE uid_owner = ?');
		$result = $query->execute(array($arguments['uid']));
		while ($item = $result->fetchRow()) {
			self::delete($item['id']);
		}
	}

	public static function post_addToGroup($arguments) {
		// TODO
	}

	public static function post_removeFromGroup($arguments) {
		$query = \OC_DB::prepare('SELECT id, share_type FROM *PREFIX*share WHERE (share_type = ? AND share_with = ?) OR (share_type = ? AND share_with = ?)');
		$result = $query->execute(array(self::SHARE_TYPE_GROUP, $arguments['gid'], self::$shareTypeGroupUserUnique, $arguments['uid']));
		while ($item = $result->fetchRow()) {
			if ($item['share_type'] == self::SHARE_TYPE_GROUP) {
				// Delete all reshares by this user of the group share
				self::delete($item['id'], true, $arguments['uid']);
			} else {
				self::delete($item['id']);
			}
		}
	}

}

/**
* Interface that apps must implement to share content.
*/
interface Share_Backend {

	/**
	* @brief Get the source of the item to be stored in the database
	* @param string Item source
	* @param string Owner of the item
	* @return mixed|array|false Source
	*
	* Return an array if the item is file dependent, the array needs two keys: 'item' and 'file'
	* Return false if the item does not exist for the user
	*
	* The formatItems() function will translate the source returned back into the item
	*/
	public function isValidSource($itemSource, $uidOwner);

	/**
	* @brief Get a unique name of the item for the specified user
	* @param string Item source
	* @param string|false User the item is being shared with
	* @param array|null List of similar item names already existing as shared items
	* @return string Target name
	*
	* This function needs to verify that the user does not already have an item with this name.
	* If it does generate a new name e.g. name_#
	*/
	public function generateTarget($itemSource, $shareWith, $exclude = null);

	/**
	* @brief Converts the shared item sources back into the item in the specified format
	* @param array Shared items
	* @param int Format 
	* @return ?
	* 
	* The items array is a 3-dimensional array with the item_source as the first key and the share id as the second key to an array with the share info.
	* The key/value pairs included in the share info depend on the function originally called: 
	* If called by getItem(s)Shared: id, item_type, item, item_source, share_type, share_with, permissions, stime, file_source
	* If called by getItem(s)SharedWith: id, item_type, item, item_source, item_target, share_type, share_with, permissions, stime, file_source, file_target
	* This function allows the backend to control the output of shared items with custom formats.
	* It is only called through calls to the public getItem(s)Shared(With) functions.
	*/
	public function formatItems($items, $format, $parameters = null);

}

/**
* Interface for share backends that share content that is dependent on files. 
* Extends the Share_Backend interface.
*/
interface Share_Backend_File_Dependent extends Share_Backend {

	/**
	* @brief Get the file path of the item
	* @param 
	* @param
	* @return
	*/
	public function getFilePath($itemSource, $uidOwner);

}

/**
* Interface for collections of of items implemented by another share backend.
* Extends the Share_Backend interface.
*/
interface Share_Backend_Collection extends Share_Backend {

	/**
	* @brief Get the sources of the children of the item
	* @param string Item source
	* @return array Returns an array of sources
	*/
	public function getChildren($itemSource);
    
}

?>
