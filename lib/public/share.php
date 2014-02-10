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

/**
 * Public interface of ownCloud for apps to use.
 * Share Class
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides the ability for apps to share their content between users.
 * Apps must create a backend class that implements OCP\Share_Backend and register it with this class.
 *
 * It provides the following hooks:
 *  - post_shared
 */
class Share {

	const SHARE_TYPE_USER = 0;
	const SHARE_TYPE_GROUP = 1;
	const SHARE_TYPE_LINK = 3;
	const SHARE_TYPE_EMAIL = 4;
	const SHARE_TYPE_CONTACT = 5;
	const SHARE_TYPE_REMOTE = 6;

	/** CRUDS permissions (Create, Read, Update, Delete, Share) using a bitmask
	 * Construct permissions for share() and setPermissions with Or (|) e.g.
	 * Give user read and update permissions: PERMISSION_READ | PERMISSION_UPDATE
	 *
	 * Check if permission is granted with And (&) e.g. Check if delete is
	 * granted: if ($permissions & PERMISSION_DELETE)
	 *
	 * Remove permissions with And (&) and Not (~) e.g. Remove the update
	 * permission: $permissions &= ~PERMISSION_UPDATE
	 *
	 * Apps are required to handle permissions on their own, this class only
	 * stores and manages the permissions of shares
	 * @see lib/public/constants.php
	 */

	const FORMAT_NONE = -1;
	const FORMAT_STATUSES = -2;
	const FORMAT_SOURCES = -3;

	const TOKEN_LENGTH = 32; // see db_structure.xml

	private static $shareTypeUserAndGroups = -1;
	private static $shareTypeGroupUserUnique = 2;
	private static $backends = array();
	private static $backendTypes = array();
	private static $isResharingAllowed;

	/**
	 * Register a sharing backend class that implements OCP\Share_Backend for an item type
	 * @param string Item type
	 * @param string Backend class
	 * @param string (optional) Depends on item type
	 * @param array (optional) List of supported file extensions if this item type depends on files
	 * @return Returns true if backend is registered or false if error
	 */
	public static function registerBackend($itemType, $class, $collectionOf = null, $supportedFileExtensions = null) {
		if (self::isEnabled()) {
			if (!isset(self::$backendTypes[$itemType])) {
				self::$backendTypes[$itemType] = array(
					'class' => $class,
					'collectionOf' => $collectionOf,
					'supportedFileExtensions' => $supportedFileExtensions
				);
				if(count(self::$backendTypes) === 1) {
					\OC_Util::addScript('core', 'share');
					\OC_Util::addStyle('core', 'share');
				}
				return true;
			}
			\OC_Log::write('OCP\Share',
				'Sharing backend '.$class.' not registered, '.self::$backendTypes[$itemType]['class']
				.' is already registered for '.$itemType,
				\OC_Log::WARN);
		}
		return false;
	}

	/**
	 * Check if the Share API is enabled
	 * @return Returns true if enabled or false
	 *
	 * The Share API is enabled by default if not configured
	 */
	public static function isEnabled() {
		if (\OC_Appconfig::getValue('core', 'shareapi_enabled', 'yes') == 'yes') {
			return true;
		}
		return false;
	}

	/**
	 * Prepare a path to be passed to DB as file_target
	 * @param string $path path
	 * @return string Prepared path
	 */
	public static function prepFileTarget( $path ) {

		// Paths in DB are stored with leading slashes, so add one if necessary
		if ( substr( $path, 0, 1 ) !== '/' ) {

			$path = '/' . $path;

		}

		return $path;

	}

	/**
	 * Find which users can access a shared item
	 * @param $path to the file
	 * @param $user owner of the file
	 * @param include owner to the list of users with access to the file
	 * @return array
	 * @note $path needs to be relative to user data dir, e.g. 'file.txt'
	 *       not '/admin/data/file.txt'
	 */
	public static function getUsersSharingFile($path, $user, $includeOwner = false) {

		$shares = array();
		$publicShare = false;
		$source = -1;
		$cache = false;

		$view = new \OC\Files\View('/' . $user . '/files');
		if ($view->file_exists($path)) {
			$meta = $view->getFileInfo($path);
		} else {
			// if the file doesn't exists yet we start with the parent folder
			$meta = $view->getFileInfo(dirname($path));
		}

		if($meta !== false) {
			$source = $meta['fileid'];
			$cache = new \OC\Files\Cache\Cache($meta['storage']);
		}

		while ($source !== -1) {

			// Fetch all shares with another user
			$query = \OC_DB::prepare(
				'SELECT `share_with`
				FROM
				`*PREFIX*share`
				WHERE
				`item_source` = ? AND `share_type` = ? AND `item_type` IN (\'file\', \'folder\')'
			);

			$result = $query->execute(array($source, self::SHARE_TYPE_USER));

			if (\OCP\DB::isError($result)) {
				\OCP\Util::writeLog('OCP\Share', \OC_DB::getErrorMessage($result), \OC_Log::ERROR);
			} else {
				while ($row = $result->fetchRow()) {
					$shares[] = $row['share_with'];
				}
			}
			// We also need to take group shares into account

			$query = \OC_DB::prepare(
				'SELECT `share_with`
				FROM
				`*PREFIX*share`
				WHERE
				`item_source` = ? AND `share_type` = ? AND `item_type` IN (\'file\', \'folder\')'
			);

			$result = $query->execute(array($source, self::SHARE_TYPE_GROUP));

			if (\OCP\DB::isError($result)) {
				\OCP\Util::writeLog('OCP\Share', \OC_DB::getErrorMessage($result), \OC_Log::ERROR);
			} else {
				while ($row = $result->fetchRow()) {
					$usersInGroup = \OC_Group::usersInGroup($row['share_with']);
					$shares = array_merge($shares, $usersInGroup);
				}
			}

			//check for public link shares
			if (!$publicShare) {
				$query = \OC_DB::prepare(
					'SELECT `share_with`
					FROM
					`*PREFIX*share`
					WHERE
					`item_source` = ? AND `share_type` = ? AND `item_type` IN (\'file\', \'folder\')'
				);

				$result = $query->execute(array($source, self::SHARE_TYPE_LINK));

				if (\OCP\DB::isError($result)) {
					\OCP\Util::writeLog('OCP\Share', \OC_DB::getErrorMessage($result), \OC_Log::ERROR);
				} else {
					if ($result->fetchRow()) {
						$publicShare = true;
					}
				}
			}

			// let's get the parent for the next round
			$meta = $cache->get((int)$source);
			if($meta !== false) {
				$source = (int)$meta['parent'];
			} else {
				$source = -1;
			}
		}
		// Include owner in list of users, if requested
		if ($includeOwner) {
			$shares[] = $user;
		}

		return array("users" => array_unique($shares), "public" => $publicShare);
	}

	/**
	 * Get the items of item type shared with the current user
	 * @param string Item type
	 * @param int Format (optional) Format type must be defined by the backend
	 * @param mixed Parameters (optional)
	 * @param int Number of items to return (optional) Returns all by default
	 * @param bool include collections (optional)
	 * @return Return depends on format
	 */
	public static function getItemsSharedWith($itemType, $format = self::FORMAT_NONE,
		$parameters = null, $limit = -1, $includeCollections = false) {
		return self::getItems($itemType, null, self::$shareTypeUserAndGroups, \OC_User::getUser(), null, $format,
			$parameters, $limit, $includeCollections);
	}

	/**
	 * Get the item of item type shared with the current user
	 * @param string $itemType
	 * @param string $itemTarget
	 * @param int $format (optional) Format type must be defined by the backend
	 * @param mixed Parameters (optional)
	 * @param bool include collections (optional)
	 * @return Return depends on format
	 */
	public static function getItemSharedWith($itemType, $itemTarget, $format = self::FORMAT_NONE,
		$parameters = null, $includeCollections = false) {
		return self::getItems($itemType, $itemTarget, self::$shareTypeUserAndGroups, \OC_User::getUser(), null, $format,
			$parameters, 1, $includeCollections);
	}

	/**
	 * Get the item of item type shared with a given user by source
	 * @param string $itemType
	 * @param string $itemSource
	 * @param string $user User user to whom the item was shared
	 * @return array Return list of items with file_target, permissions and expiration
	 */
	public static function getItemSharedWithUser($itemType, $itemSource, $user) {

		$shares = array();

		// first check if there is a db entry for the specific user
		$query = \OC_DB::prepare(
				'SELECT `file_target`, `permissions`, `expiration`
					FROM
					`*PREFIX*share`
					WHERE
					`item_source` = ? AND `item_type` = ? AND `share_with` = ?'
				);

		$result = \OC_DB::executeAudited($query, array($itemSource, $itemType, $user));

		while ($row = $result->fetchRow()) {
			$shares[] = $row;
		}

		//if didn't found a result than let's look for a group share.
		if(empty($shares)) {
			$groups = \OC_Group::getUserGroups($user);

			$query = \OC_DB::prepare(
					'SELECT `file_target`, `permissions`, `expiration`
						FROM
						`*PREFIX*share`
						WHERE
						`item_source` = ? AND `item_type` = ? AND `share_with` in (?)'
					);

			$result = \OC_DB::executeAudited($query, array($itemSource, $itemType, implode(',', $groups)));

			while ($row = $result->fetchRow()) {
				$shares[] = $row;
			}
		}

		return $shares;

	}

	/**
	 * Get the item of item type shared with the current user by source
	 * @param string Item type
	 * @param string Item source
	 * @param int Format (optional) Format type must be defined by the backend
	 * @param mixed Parameters
	 * @param bool include collections
	 * @return Return depends on format
	 */
	public static function getItemSharedWithBySource($itemType, $itemSource, $format = self::FORMAT_NONE,
		$parameters = null, $includeCollections = false) {
		return self::getItems($itemType, $itemSource, self::$shareTypeUserAndGroups, \OC_User::getUser(), null, $format,
			$parameters, 1, $includeCollections, true);
	}

	/**
	 * Get the item of item type shared by a link
	 * @param string Item type
	 * @param string Item source
	 * @param string Owner of link
	 * @return Item
	 */
	public static function getItemSharedWithByLink($itemType, $itemSource, $uidOwner) {
		return self::getItems($itemType, $itemSource, self::SHARE_TYPE_LINK, null, $uidOwner, self::FORMAT_NONE,
			null, 1);
	}

	/**
	 * Based on the given token the share information will be returned - password protected shares will be verified
	 * @param string $token
	 * @return array | bool false will be returned in case the token is unknown or unauthorized
	 */
	public static function getShareByToken($token, $checkPasswordProtection = true) {
		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*share` WHERE `token` = ?', 1);
		$result = $query->execute(array($token));
		if (\OC_DB::isError($result)) {
			\OC_Log::write('OCP\Share', \OC_DB::getErrorMessage($result) . ', token=' . $token, \OC_Log::ERROR);
		}
		$row = $result->fetchRow();
		if ($row === false) {
			return false;
		}
		if (is_array($row) and self::expireItem($row)) {
			return false;
		}

		// password protected shares need to be authenticated
		if ($checkPasswordProtection && !\OCP\Share::checkPasswordProtectedShare($row)) {
			return false;
		}

		return $row;
	}

	/**
	 * resolves reshares down to the last real share
	 * @param $linkItem
	 * @return $fileOwner
	 */
	public static function resolveReShare($linkItem)
	{
		if (isset($linkItem['parent'])) {
			$parent = $linkItem['parent'];
			while (isset($parent)) {
				$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*share` WHERE `id` = ?', 1);
				$item = $query->execute(array($parent))->fetchRow();
				if (isset($item['parent'])) {
					$parent = $item['parent'];
				} else {
					return $item;
				}
			}
		}
		return $linkItem;
	}


	/**
	 * Get the shared items of item type owned by the current user
	 * @param string Item type
	 * @param int Format (optional) Format type must be defined by the backend
	 * @param mixed Parameters
	 * @param int Number of items to return (optional) Returns all by default
	 * @param bool include collections
	 * @return Return depends on format
	 */
	public static function getItemsShared($itemType, $format = self::FORMAT_NONE, $parameters = null,
		$limit = -1, $includeCollections = false) {
		return self::getItems($itemType, null, null, null, \OC_User::getUser(), $format,
			$parameters, $limit, $includeCollections);
	}

	/**
	 * Get the shared item of item type owned by the current user
	 * @param string Item type
	 * @param string Item source
	 * @param int Format (optional) Format type must be defined by the backend
	 * @param mixed Parameters
	 * @param bool include collections
	 * @return Return depends on format
	 */
	public static function getItemShared($itemType, $itemSource, $format = self::FORMAT_NONE,
	                                     $parameters = null, $includeCollections = false) {
		return self::getItems($itemType, $itemSource, null, null, \OC_User::getUser(), $format,
			$parameters, -1, $includeCollections);
	}

	/**
	 * Get all users an item is shared with
	 * @param string Item type
	 * @param string Item source
	 * @param string Owner
	 * @param bool Include collections
	 * @praram bool check expire date
	 * @return Return array of users
	 */
	public static function getUsersItemShared($itemType, $itemSource, $uidOwner, $includeCollections = false, $checkExpireDate = true) {

		$users = array();
		$items = self::getItems($itemType, $itemSource, null, null, $uidOwner, self::FORMAT_NONE, null, -1, $includeCollections, false, $checkExpireDate);
		if ($items) {
			foreach ($items as $item) {
				if ((int)$item['share_type'] === self::SHARE_TYPE_USER) {
					$users[] = $item['share_with'];
				} else if ((int)$item['share_type'] === self::SHARE_TYPE_GROUP) {
					$users = array_merge($users, \OC_Group::usersInGroup($item['share_with']));
				}
			}
		}
		return $users;
	}

	/**
	 * Share an item with a user, group, or via private link
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $shareType SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string $shareWith User or group the item is being shared with
	 * @param int $permissions CRUDS
	 * @param null $itemSourceName
	 * @throws \Exception
	 * @internal param \OCP\Item $string type
	 * @internal param \OCP\Item $string source
	 * @internal param \OCP\SHARE_TYPE_USER $int , SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @internal param \OCP\User $string or group the item is being shared with
	 * @internal param \OCP\CRUDS $int permissions
	 * @return bool|string Returns true on success or false on failure, Returns token on success for links
	 */
	public static function shareItem($itemType, $itemSource, $shareType, $shareWith, $permissions, $itemSourceName = null) {
		$uidOwner = \OC_User::getUser();
		$sharingPolicy = \OC_Appconfig::getValue('core', 'shareapi_share_policy', 'global');

		if (is_null($itemSourceName)) {
			$itemSourceName = $itemSource;
		}

		// Verify share type and sharing conditions are met
		if ($shareType === self::SHARE_TYPE_USER) {
			if ($shareWith == $uidOwner) {
				$message = 'Sharing '.$itemSourceName.' failed, because the user '.$shareWith.' is the item owner';
				\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
				throw new \Exception($message);
			}
			if (!\OC_User::userExists($shareWith)) {
				$message = 'Sharing '.$itemSourceName.' failed, because the user '.$shareWith.' does not exist';
				\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
				throw new \Exception($message);
			}
			if ($sharingPolicy == 'groups_only') {
				$inGroup = array_intersect(\OC_Group::getUserGroups($uidOwner), \OC_Group::getUserGroups($shareWith));
				if (empty($inGroup)) {
					$message = 'Sharing '.$itemSourceName.' failed, because the user '
						.$shareWith.' is not a member of any groups that '.$uidOwner.' is a member of';
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				}
			}
			// Check if the item source is already shared with the user, either from the same owner or a different user
			if ($checkExists = self::getItems($itemType, $itemSource, self::$shareTypeUserAndGroups,
				$shareWith, null, self::FORMAT_NONE, null, 1, true, true)) {
				// Only allow the same share to occur again if it is the same
				// owner and is not a user share, this use case is for increasing
				// permissions for a specific user
				if ($checkExists['uid_owner'] != $uidOwner || $checkExists['share_type'] == $shareType) {
					$message = 'Sharing '.$itemSourceName.' failed, because this item is already shared with '.$shareWith;
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				}
			}
		} else if ($shareType === self::SHARE_TYPE_GROUP) {
			if (!\OC_Group::groupExists($shareWith)) {
				$message = 'Sharing '.$itemSourceName.' failed, because the group '.$shareWith.' does not exist';
				\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
				throw new \Exception($message);
			}
			if ($sharingPolicy == 'groups_only' && !\OC_Group::inGroup($uidOwner, $shareWith)) {
				$message = 'Sharing '.$itemSourceName.' failed, because '
					.$uidOwner.' is not a member of the group '.$shareWith;
				\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
				throw new \Exception($message);
			}
			// Check if the item source is already shared with the group, either from the same owner or a different user
			// The check for each user in the group is done inside the put() function
			if ($checkExists = self::getItems($itemType, $itemSource, self::SHARE_TYPE_GROUP, $shareWith,
				null, self::FORMAT_NONE, null, 1, true, true)) {
				// Only allow the same share to occur again if it is the same
				// owner and is not a group share, this use case is for increasing
				// permissions for a specific user
				if ($checkExists['uid_owner'] != $uidOwner || $checkExists['share_type'] == $shareType) {
					$message = 'Sharing '.$itemSourceName.' failed, because this item is already shared with '.$shareWith;
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				}
			}
			// Convert share with into an array with the keys group and users
			$group = $shareWith;
			$shareWith = array();
			$shareWith['group'] = $group;
			$shareWith['users'] = array_diff(\OC_Group::usersInGroup($group), array($uidOwner));
		} else if ($shareType === self::SHARE_TYPE_LINK) {
			if (\OC_Appconfig::getValue('core', 'shareapi_allow_links', 'yes') == 'yes') {
				// when updating a link share
				if ($checkExists = self::getItems($itemType, $itemSource, self::SHARE_TYPE_LINK, null,
					$uidOwner, self::FORMAT_NONE, null, 1)) {
					// remember old token
					$oldToken = $checkExists['token'];
					$oldPermissions = $checkExists['permissions'];
					//delete the old share
					self::delete($checkExists['id']);
				}

				// Generate hash of password - same method as user passwords
				if (isset($shareWith)) {
					$forcePortable = (CRYPT_BLOWFISH != 1);
					$hasher = new \PasswordHash(8, $forcePortable);
					$shareWith = $hasher->HashPassword($shareWith.\OC_Config::getValue('passwordsalt', ''));
				} else {
					// reuse the already set password, but only if we change permissions
					// otherwise the user disabled the password protection
					if ($checkExists && (int)$permissions !== (int)$oldPermissions) {
						$shareWith = $checkExists['share_with'];
					}
				}

				// Generate token
				if (isset($oldToken)) {
					$token = $oldToken;
				} else {
					$token = \OC_Util::generateRandomBytes(self::TOKEN_LENGTH);
				}
				$result = self::put($itemType, $itemSource, $shareType, $shareWith, $uidOwner, $permissions,
					null, $token, $itemSourceName);
				if ($result) {
					return $token;
				} else {
					return false;
				}
			}
			$message = 'Sharing '.$itemSourceName.' failed, because sharing with links is not allowed';
			\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
			throw new \Exception($message);
			return false;
// 		} else if ($shareType === self::SHARE_TYPE_CONTACT) {
// 			if (!\OC_App::isEnabled('contacts')) {
// 				$message = 'Sharing '.$itemSource.' failed, because the contacts app is not enabled';
// 				\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
// 				return false;
// 			}
// 			$vcard = \OC_Contacts_App::getContactVCard($shareWith);
// 			if (!isset($vcard)) {
// 				$message = 'Sharing '.$itemSource.' failed, because the contact does not exist';
// 				\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
// 				throw new \Exception($message);
// 			}
// 			$details = \OC_Contacts_VCard::structureContact($vcard);
// 			// TODO Add ownCloud user to contacts vcard
// 			if (!isset($details['EMAIL'])) {
// 				$message = 'Sharing '.$itemSource.' failed, because no email address is associated with the contact';
// 				\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
// 				throw new \Exception($message);
// 			}
// 			return self::shareItem($itemType, $itemSource, self::SHARE_TYPE_EMAIL, $details['EMAIL'], $permissions);
		} else {
			// Future share types need to include their own conditions
			$message = 'Share type '.$shareType.' is not valid for '.$itemSource;
			\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
			throw new \Exception($message);
		}
		// If the item is a folder, scan through the folder looking for equivalent item types
// 		if ($itemType == 'folder') {
// 			$parentFolder = self::put('folder', $itemSource, $shareType, $shareWith, $uidOwner, $permissions, true);
// 			if ($parentFolder && $files = \OC\Files\Filesystem::getDirectoryContent($itemSource)) {
// 				for ($i = 0; $i < count($files); $i++) {
// 					$name = substr($files[$i]['name'], strpos($files[$i]['name'], $itemSource) - strlen($itemSource));
// 					if ($files[$i]['mimetype'] == 'httpd/unix-directory'
// 						&& $children = \OC\Files\Filesystem::getDirectoryContent($name, '/')
// 					) {
// 						// Continue scanning into child folders
// 						array_push($files, $children);
// 					} else {
// 						// Check file extension for an equivalent item type to convert to
// 						$extension = strtolower(substr($itemSource, strrpos($itemSource, '.') + 1));
// 						foreach (self::$backends as $type => $backend) {
// 							if (isset($backend->dependsOn) && $backend->dependsOn == 'file' && isset($backend->supportedFileExtensions) && in_array($extension, $backend->supportedFileExtensions)) {
// 								$itemType = $type;
// 								break;
// 							}
// 						}
// 						// Pass on to put() to check if this item should be converted, the item won't be inserted into the database unless it can be converted
// 						self::put($itemType, $name, $shareType, $shareWith, $uidOwner, $permissions, $parentFolder);
// 					}
// 				}
// 				return true;
// 			}
// 			return false;
// 		} else {
			// Put the item into the database
			return self::put($itemType, $itemSource, $shareType, $shareWith, $uidOwner, $permissions, null, null, $itemSourceName);
// 		}
	}

	/**
	 * Unshare an item from a user, group, or delete a private link
	 * @param string Item type
	 * @param string Item source
	 * @param int SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string User or group the item is being shared with
	 * @return Returns true on success or false on failure
	 */
	public static function unshare($itemType, $itemSource, $shareType, $shareWith) {
		if ($item = self::getItems($itemType, $itemSource, $shareType, $shareWith, \OC_User::getUser(),
			self::FORMAT_NONE, null, 1)) {
			self::unshareItem($item);
			return true;
		}
		return false;
	}

	/**
	 * Unshare an item from all users, groups, and remove all links
	 * @param string Item type
	 * @param string Item source
	 * @return Returns true on success or false on failure
	 */
	public static function unshareAll($itemType, $itemSource) {
		// Get all of the owners of shares of this item.
		$query = \OC_DB::prepare( 'SELECT `uid_owner` from `*PREFIX*share` WHERE `item_type`=? AND `item_source`=?' );
		$result = $query->execute(array($itemType, $itemSource));
		$shares = array();
		// Add each owner's shares to the array of all shares for this item.
		while ($row = $result->fetchRow()) {
			$shares = array_merge($shares, self::getItems($itemType, $itemSource, null, null, $row['uid_owner']));
		}
		if (!empty($shares)) {
			// Pass all the vars we have for now, they may be useful
			$hookParams = array(
				'itemType' => $itemType,
				'itemSource' => $itemSource,
				'shares' => $shares,
			);
			\OC_Hook::emit('OCP\Share', 'pre_unshareAll', $hookParams);
			foreach ($shares as $share) {
				self::unshareItem($share);
			}
			\OC_Hook::emit('OCP\Share', 'post_unshareAll', $hookParams);
			return true;
		}
		return false;
	}

	/**
	 * Unshare an item shared with the current user
	 * @param string Item type
	 * @param string Item target
	 * @return Returns true on success or false on failure
	 *
	 * Unsharing from self is not allowed for items inside collections
	 */
	public static function unshareFromSelf($itemType, $itemTarget) {
		if ($item = self::getItemSharedWith($itemType, $itemTarget)) {
			if ((int)$item['share_type'] === self::SHARE_TYPE_GROUP) {
				// Insert an extra row for the group share and set permission
				// to 0 to prevent it from showing up for the user
				$query = \OC_DB::prepare('INSERT INTO `*PREFIX*share`'
					.' (`item_type`, `item_source`, `item_target`, `parent`, `share_type`,'
					.' `share_with`, `uid_owner`, `permissions`, `stime`, `file_source`, `file_target`)'
					.' VALUES (?,?,?,?,?,?,?,?,?,?,?)');
				$query->execute(array($item['item_type'], $item['item_source'], $item['item_target'],
					$item['id'], self::$shareTypeGroupUserUnique,
					\OC_User::getUser(), $item['uid_owner'], 0, $item['stime'], $item['file_source'],
					$item['file_target']));
				\OC_DB::insertid('*PREFIX*share');
				// Delete all reshares by this user of the group share
				self::delete($item['id'], true, \OC_User::getUser());
			} else if ((int)$item['share_type'] === self::$shareTypeGroupUserUnique) {
				// Set permission to 0 to prevent it from showing up for the user
				$query = \OC_DB::prepare('UPDATE `*PREFIX*share` SET `permissions` = ? WHERE `id` = ?');
				$query->execute(array(0, $item['id']));
				self::delete($item['id'], true);
			} else {
				self::delete($item['id']);
			}
			return true;
		}
		return false;
	}
	/**
	 * sent status if users got informed by mail about share
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $shareType SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param bool $status
	 */
	public static function setSendMailStatus($itemType, $itemSource, $shareType, $status) {
		$status = $status ? 1 : 0;

		$query = \OC_DB::prepare(
				'UPDATE `*PREFIX*share`
					SET `mail_send` = ?
					WHERE `item_type` = ? AND `item_source` = ? AND `share_type` = ?');

		$result = $query->execute(array($status, $itemType, $itemSource, $shareType));

		if($result === false) {
			\OC_Log::write('OCP\Share', 'Couldn\'t set send mail status', \OC_Log::ERROR);
		}
	}

	/**
	 * Set the permissions of an item for a specific user or group
	 * @param string Item type
	 * @param string Item source
	 * @param int SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string User or group the item is being shared with
	 * @param int CRUDS permissions
	 * @return Returns true on success or false on failure
	 */
	public static function setPermissions($itemType, $itemSource, $shareType, $shareWith, $permissions) {
		if ($item = self::getItems($itemType, $itemSource, $shareType, $shareWith,
			\OC_User::getUser(), self::FORMAT_NONE, null, 1, false)) {
			// Check if this item is a reshare and verify that the permissions
			// granted don't exceed the parent shared item
			if (isset($item['parent'])) {
				$query = \OC_DB::prepare('SELECT `permissions` FROM `*PREFIX*share` WHERE `id` = ?', 1);
				$result = $query->execute(array($item['parent']))->fetchRow();
				if (~(int)$result['permissions'] & $permissions) {
					$message = 'Setting permissions for '.$itemSource.' failed,'
						.' because the permissions exceed permissions granted to '.\OC_User::getUser();
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				}
			}
			$query = \OC_DB::prepare('UPDATE `*PREFIX*share` SET `permissions` = ? WHERE `id` = ?');
			$query->execute(array($permissions, $item['id']));
			if ($itemType === 'file' || $itemType === 'folder') {
				\OC_Hook::emit('OCP\Share', 'post_update_permissions', array(
					'itemType' => $itemType,
					'itemSource' => $itemSource,
					'shareType' => $shareType,
					'shareWith' => $shareWith,
					'uidOwner' => \OC_User::getUser(),
					'permissions' => $permissions,
					'path' => $item['path'],
				));
			}
			// Check if permissions were removed
			if ($item['permissions'] & ~$permissions) {
				// If share permission is removed all reshares must be deleted
				if (($item['permissions'] & PERMISSION_SHARE) && (~$permissions & PERMISSION_SHARE)) {
					self::delete($item['id'], true);
				} else {
					$ids = array();
					$parents = array($item['id']);
					while (!empty($parents)) {
						$parents = "'".implode("','", $parents)."'";
						$query = \OC_DB::prepare('SELECT `id`, `permissions` FROM `*PREFIX*share`'
							.' WHERE `parent` IN ('.$parents.')');
						$result = $query->execute();
						// Reset parents array, only go through loop again if
						// items are found that need permissions removed
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
						// TODO this should be done with Doctrine platform objects
						if (\OC_Config::getValue( "dbtype") === 'oci') {
							$andOp = 'BITAND(`permissions`, ?)';
						} else {
							$andOp = '`permissions` & ?';
						}
						$query = \OC_DB::prepare('UPDATE `*PREFIX*share` SET `permissions` = '.$andOp
							.' WHERE `id` IN ('.$ids.')');
						$query->execute(array($permissions));
					}
				}
			}
			return true;
		}
		$message = 'Setting permissions for '.$itemSource.' failed, because the item was not found';
		\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
		throw new \Exception($message);
	}

	/**
	 * Set expiration date for a share
	 * @param string $itemType
	 * @param string $itemSource
	 * @param string $date expiration date
	 * @return Share_Backend
	 */
	public static function setExpirationDate($itemType, $itemSource, $date) {
		if ($items = self::getItems($itemType, $itemSource, null, null, \OC_User::getUser(),
			self::FORMAT_NONE, null, -1, false)) {
			if (!empty($items)) {
				if ($date == '') {
					$date = null;
				} else {
					$date = new \DateTime($date);
				}
				$query = \OC_DB::prepare('UPDATE `*PREFIX*share` SET `expiration` = ? WHERE `id` = ?');
				$query->bindValue(1, $date, 'datetime');
				foreach ($items as $item) {
					$query->bindValue(2, (int) $item['id']);
					$query->execute();
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks whether a share has expired, calls unshareItem() if yes.
	 * @param array $item Share data (usually database row)
	 * @return bool True if item was expired, false otherwise.
	 */
	protected static function expireItem(array $item) {
		if (!empty($item['expiration'])) {
			$now = new \DateTime();
			$expires = new \DateTime($item['expiration']);
			if ($now > $expires) {
				self::unshareItem($item);
				return true;
			}
		}
		return false;
	}

	/**
	 * Unshares a share given a share data array
	 * @param array $item Share data (usually database row)
	 * @return null
	 */
	protected static function unshareItem(array $item) {
		// Pass all the vars we have for now, they may be useful
		$hookParams = array(
			'itemType'      => $item['item_type'],
			'itemSource'    => $item['item_source'],
			'shareType'     => $item['share_type'],
			'shareWith'     => $item['share_with'],
			'itemParent'    => $item['parent'],
			'uidOwner'      => $item['uid_owner'],
		);

		\OC_Hook::emit('OCP\Share', 'pre_unshare', $hookParams + array(
			'fileSource'	=> $item['file_source'],
		));
		self::delete($item['id']);
		\OC_Hook::emit('OCP\Share', 'post_unshare', $hookParams);
	}

	/**
	 * Get the backend class for the specified item type
	 * @param string $itemType
	 * @return Share_Backend
	 */
	public static function getBackend($itemType) {
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
	 * Check if resharing is allowed
	 * @return Returns true if allowed or false
	 *
	 * Resharing is allowed by default if not configured
	 */
	private static function isResharingAllowed() {
		if (!isset(self::$isResharingAllowed)) {
			if (\OC_Appconfig::getValue('core', 'shareapi_allow_resharing', 'yes') == 'yes') {
				self::$isResharingAllowed = true;
			} else {
				self::$isResharingAllowed = false;
			}
		}
		return self::$isResharingAllowed;
	}

	/**
	 * Get a list of collection item types for the specified item type
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
		// TODO Add option for collections to be collection of themselves, only 'folder' does it now...
		if (!self::getBackend($itemType) instanceof Share_Backend_Collection || $itemType != 'folder') {
			unset($collectionTypes[0]);
		}
		// Return array if collections were found or the item type is a
		// collection itself - collections can be inside collections
		if (count($collectionTypes) > 0) {
			return $collectionTypes;
		}
		return false;
	}

	/**
	 * Get shared items from the database
	 * @param string Item type
	 * @param string Item source or target (optional)
	 * @param int SHARE_TYPE_USER, SHARE_TYPE_GROUP, SHARE_TYPE_LINK, $shareTypeUserAndGroups, or $shareTypeGroupUserUnique
	 * @param string User or group the item is being shared with
	 * @param string User that is the owner of shared items (optional)
	 * @param int Format to convert items to with formatItems()
	 * @param mixed Parameters to pass to formatItems()
	 * @param int Number of items to return, -1 to return all matches (optional)
	 * @param bool Include collection item types (optional)
	 * @param bool TODO (optional)
	 * @prams bool check expire date
	 * @return mixed
	 *
	 * See public functions getItem(s)... for parameter usage
	 *
	 */
	private static function getItems($itemType, $item = null, $shareType = null, $shareWith = null,
		$uidOwner = null, $format = self::FORMAT_NONE, $parameters = null, $limit = -1,
		$includeCollections = false, $itemShareWithBySource = false, $checkExpireDate  = true) {
		if (!self::isEnabled()) {
			if ($limit == 1 || (isset($uidOwner) && isset($item))) {
				return false;
			} else {
				return array();
			}
		}
		$backend = self::getBackend($itemType);
		$collectionTypes = false;
		// Get filesystem root to add it to the file target and remove from the
		// file source, match file_source with the file cache
		if ($itemType == 'file' || $itemType == 'folder') {
			if(!is_null($uidOwner)) {
				$root = \OC\Files\Filesystem::getRoot();
			} else {
				$root = '';
			}
			$where = 'INNER JOIN `*PREFIX*filecache` ON `file_source` = `*PREFIX*filecache`.`fileid`';
			if (!isset($item)) {
				$where .= ' WHERE `file_target` IS NOT NULL';
			}
			$fileDependent = true;
			$queryArgs = array();
		} else {
			$fileDependent = false;
			$root = '';
			if ($includeCollections && !isset($item) && ($collectionTypes = self::getCollectionItemTypes($itemType))) {
				// If includeCollections is true, find collections of this item type, e.g. a music album contains songs
				if (!in_array($itemType, $collectionTypes)) {
					$itemTypes = array_merge(array($itemType), $collectionTypes);
				} else {
					$itemTypes = $collectionTypes;
				}
				$placeholders = join(',', array_fill(0, count($itemTypes), '?'));
				$where = ' WHERE `item_type` IN ('.$placeholders.'))';
				$queryArgs = $itemTypes;
			} else {
				$where = ' WHERE `item_type` = ?';
				$queryArgs = array($itemType);
			}
		}
		if (\OC_Appconfig::getValue('core', 'shareapi_allow_links', 'yes') !== 'yes') {
			$where .= ' AND `share_type` != ?';
			$queryArgs[] = self::SHARE_TYPE_LINK;
		}
		if (isset($shareType)) {
			// Include all user and group items
			if ($shareType == self::$shareTypeUserAndGroups && isset($shareWith)) {
				$where .= ' AND `share_type` IN (?,?,?)';
				$queryArgs[] = self::SHARE_TYPE_USER;
				$queryArgs[] = self::SHARE_TYPE_GROUP;
				$queryArgs[] = self::$shareTypeGroupUserUnique;
				$userAndGroups = array_merge(array($shareWith), \OC_Group::getUserGroups($shareWith));
				$placeholders = join(',', array_fill(0, count($userAndGroups), '?'));
				$where .= ' AND `share_with` IN ('.$placeholders.')';
				$queryArgs = array_merge($queryArgs, $userAndGroups);
				// Don't include own group shares
				$where .= ' AND `uid_owner` != ?';
				$queryArgs[] = $shareWith;
			} else {
				$where .= ' AND `share_type` = ?';
				$queryArgs[] = $shareType;
				if (isset($shareWith)) {
					$where .= ' AND `share_with` = ?';
					$queryArgs[] = $shareWith;
				}
			}
		}
		if (isset($uidOwner)) {
			$where .= ' AND `uid_owner` = ?';
			$queryArgs[] = $uidOwner;
			if (!isset($shareType)) {
				// Prevent unique user targets for group shares from being selected
				$where .= ' AND `share_type` != ?';
				$queryArgs[] = self::$shareTypeGroupUserUnique;
			}
			if ($itemType == 'file' || $itemType == 'folder') {
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
			if ($includeCollections && $collectionTypes = self::getCollectionItemTypes($itemType)) {
				$where .= ' AND (';
			} else {
				$where .= ' AND';
			}
			// If looking for own shared items, check item_source else check item_target
			if (isset($uidOwner) || $itemShareWithBySource) {
				// If item type is a file, file source needs to be checked in case the item was converted
				if ($itemType == 'file' || $itemType == 'folder') {
					$where .= ' `file_source` = ?';
					$column = 'file_source';
				} else {
					$where .= ' `item_source` = ?';
					$column = 'item_source';
				}
			} else {
				if ($itemType == 'file' || $itemType == 'folder') {
					$where .= ' `file_target` = ?';
					$item = \OC\Files\Filesystem::normalizePath($item);
				} else {
					$where .= ' `item_target` = ?';
				}
			}
			$queryArgs[] = $item;
			if ($includeCollections && $collectionTypes) {
				$placeholders = join(',', array_fill(0, count($collectionTypes), '?'));
				$where .= ' OR `item_type` IN ('.$placeholders.'))';
				$queryArgs = array_merge($queryArgs, $collectionTypes);
			}
		}
		if ($limit != -1 && !$includeCollections) {
			if ($shareType == self::$shareTypeUserAndGroups) {
				// Make sure the unique user target is returned if it exists,
				// unique targets should follow the group share in the database
				// If the limit is not 1, the filtering can be done later
				$where .= ' ORDER BY `*PREFIX*share`.`id` DESC';
			}
			// The limit must be at least 3, because filtering needs to be done
			if ($limit < 3) {
				$queryLimit = 3;
			} else {
				$queryLimit = $limit;
			}
		} else {
			$queryLimit = null;
		}
		// TODO Optimize selects
		if ($format == self::FORMAT_STATUSES) {
			if ($itemType == 'file' || $itemType == 'folder') {
				$select = '`*PREFIX*share`.`id`, `item_type`, `item_source`, `*PREFIX*share`.`parent`,'
					.' `share_type`, `file_source`, `path`, `expiration`, `storage`, `share_with`, `mail_send`, `uid_owner`';
			} else {
				$select = '`id`, `item_type`, `item_source`, `parent`, `share_type`, `share_with`, `expiration`, `mail_send`, `uid_owner`';
			}
		} else {
			if (isset($uidOwner)) {
				if ($itemType == 'file' || $itemType == 'folder') {
					$select = '`*PREFIX*share`.`id`, `item_type`, `item_source`, `*PREFIX*share`.`parent`,'
						.' `share_type`, `share_with`, `file_source`, `path`, `permissions`, `stime`,'
						.' `expiration`, `token`, `storage`, `mail_send`, `uid_owner`';
				} else {
					$select = '`id`, `item_type`, `item_source`, `parent`, `share_type`, `share_with`, `permissions`,'
						.' `stime`, `file_source`, `expiration`, `token`, `mail_send`, `uid_owner`';
				}
			} else {
				if ($fileDependent) {
					if (($itemType == 'file' || $itemType == 'folder')
						&& $format == \OC_Share_Backend_File::FORMAT_GET_FOLDER_CONTENTS
						|| $format == \OC_Share_Backend_File::FORMAT_FILE_APP_ROOT
					) {
						$select = '`*PREFIX*share`.`id`, `item_type`, `item_source`, `*PREFIX*share`.`parent`, `uid_owner`, '
							.'`share_type`, `share_with`, `file_source`, `path`, `file_target`, '
							.'`permissions`, `expiration`, `storage`, `*PREFIX*filecache`.`parent` as `file_parent`, '
							.'`name`, `mtime`, `mimetype`, `mimepart`, `size`, `unencrypted_size`, `encrypted`, `etag`, `mail_send`';
					} else {
						$select = '`*PREFIX*share`.`id`, `item_type`, `item_source`, `item_target`,
							`*PREFIX*share`.`parent`, `share_type`, `share_with`, `uid_owner`,
							`file_source`, `path`, `file_target`, `permissions`, `stime`, `expiration`, `token`, `storage`, `mail_send`';
					}
				} else {
					$select = '*';
				}
			}
		}
		$root = strlen($root);
		$query = \OC_DB::prepare('SELECT '.$select.' FROM `*PREFIX*share` '.$where, $queryLimit);
		$result = $query->execute($queryArgs);
		if (\OC_DB::isError($result)) {
			\OC_Log::write('OCP\Share',
				\OC_DB::getErrorMessage($result) . ', select=' . $select . ' where=' . $where,
				\OC_Log::ERROR);
		}
		$items = array();
		$targets = array();
		$switchedItems = array();
		$mounts = array();
		while ($row = $result->fetchRow()) {
			if (isset($row['id'])) {
				$row['id']=(int)$row['id'];
			}
			if (isset($row['share_type'])) {
				$row['share_type']=(int)$row['share_type'];
			}
			if (isset($row['parent'])) {
				$row['parent']=(int)$row['parent'];
			}
			if (isset($row['file_parent'])) {
				$row['file_parent']=(int)$row['file_parent'];
			}
			if (isset($row['file_source'])) {
				$row['file_source']=(int)$row['file_source'];
			}
			if (isset($row['permissions'])) {
				$row['permissions']=(int)$row['permissions'];
			}
			if (isset($row['storage'])) {
				$row['storage']=(int)$row['storage'];
			}
			if (isset($row['stime'])) {
				$row['stime']=(int)$row['stime'];
			}
			// Filter out duplicate group shares for users with unique targets
			if ($row['share_type'] == self::$shareTypeGroupUserUnique && isset($items[$row['parent']])) {
				$row['share_type'] = self::SHARE_TYPE_GROUP;
				$row['share_with'] = $items[$row['parent']]['share_with'];
				// Remove the parent group share
				unset($items[$row['parent']]);
				if ($row['permissions'] == 0) {
					continue;
				}
			} else if (!isset($uidOwner)) {
				// Check if the same target already exists
				if (isset($targets[$row[$column]])) {
					// Check if the same owner shared with the user twice
					// through a group and user share - this is allowed
					$id = $targets[$row[$column]];
					if (isset($items[$id]) && $items[$id]['uid_owner'] == $row['uid_owner']) {
						// Switch to group share type to ensure resharing conditions aren't bypassed
						if ($items[$id]['share_type'] != self::SHARE_TYPE_GROUP) {
							$items[$id]['share_type'] = self::SHARE_TYPE_GROUP;
							$items[$id]['share_with'] = $row['share_with'];
						}
						// Switch ids if sharing permission is granted on only
						// one share to ensure correct parent is used if resharing
						if (~(int)$items[$id]['permissions'] & PERMISSION_SHARE
							&& (int)$row['permissions'] & PERMISSION_SHARE) {
							$items[$row['id']] = $items[$id];
							$switchedItems[$id] = $row['id'];
							unset($items[$id]);
							$id = $row['id'];
						}
						// Combine the permissions for the item
						$items[$id]['permissions'] |= (int)$row['permissions'];
						continue;
					}
				} else {
					$targets[$row[$column]] = $row['id'];
				}
			}
			// Remove root from file source paths if retrieving own shared items
			if (isset($uidOwner) && isset($row['path'])) {
				if (isset($row['parent'])) {
					$row['path'] = '/Shared/'.basename($row['path']);
				} else {
					if (!isset($mounts[$row['storage']])) {
						$mountPoints = \OC\Files\Filesystem::getMountByNumericId($row['storage']);
						if (is_array($mountPoints)) {
							$mounts[$row['storage']] = current($mountPoints);
						}
					}
					if ($mounts[$row['storage']]) {
						$path = $mounts[$row['storage']]->getMountPoint().$row['path'];
						$row['path'] = substr($path, $root);
					}
				}
			}
			if($checkExpireDate) {
				if (self::expireItem($row)) {
					continue;
				}
			}
			// Check if resharing is allowed, if not remove share permission
			if (isset($row['permissions']) && !self::isResharingAllowed()) {
				$row['permissions'] &= ~PERMISSION_SHARE;
			}
			// Add display names to result
			if ( isset($row['share_with']) && $row['share_with'] != '') {
				$row['share_with_displayname'] = \OCP\User::getDisplayName($row['share_with']);
			}
			if ( isset($row['uid_owner']) && $row['uid_owner'] != '') {
				$row['displayname_owner'] = \OCP\User::getDisplayName($row['uid_owner']);
			}

			$items[$row['id']] = $row;
		}
		if (!empty($items)) {
			$collectionItems = array();
			foreach ($items as &$row) {
				// Return only the item instead of a 2-dimensional array
				if ($limit == 1 && $row[$column] == $item && ($row['item_type'] == $itemType || $itemType == 'file')) {
					if ($format == self::FORMAT_NONE) {
						return $row;
					} else {
						break;
					}
				}
				// Check if this is a collection of the requested item type
				if ($includeCollections && $collectionTypes && in_array($row['item_type'], $collectionTypes)) {
					if (($collectionBackend = self::getBackend($row['item_type']))
						&& $collectionBackend instanceof Share_Backend_Collection) {
						// Collections can be inside collections, check if the item is a collection
						if (isset($item) && $row['item_type'] == $itemType && $row[$column] == $item) {
							$collectionItems[] = $row;
						} else {
							$collection = array();
							$collection['item_type'] = $row['item_type'];
							if ($row['item_type'] == 'file' || $row['item_type'] == 'folder') {
								$collection['path'] = basename($row['path']);
							}
							$row['collection'] = $collection;
							// Fetch all of the children sources
							$children = $collectionBackend->getChildren($row[$column]);
							foreach ($children as $child) {
								$childItem = $row;
								$childItem['item_type'] = $itemType;
								if ($row['item_type'] != 'file' && $row['item_type'] != 'folder') {
									$childItem['item_source'] = $child['source'];
									$childItem['item_target'] = $child['target'];
								}
								if ($backend instanceof Share_Backend_File_Dependent) {
									if ($row['item_type'] == 'file' || $row['item_type'] == 'folder') {
										$childItem['file_source'] = $child['source'];
									} else {
										$meta = \OC\Files\Filesystem::getFileInfo($child['file_path']);
										$childItem['file_source'] = $meta['fileid'];
									}
									$childItem['file_target'] =
										\OC\Files\Filesystem::normalizePath($child['file_path']);
								}
								if (isset($item)) {
									if ($childItem[$column] == $item) {
										// Return only the item instead of a 2-dimensional array
										if ($limit == 1) {
											if ($format == self::FORMAT_NONE) {
												return $childItem;
											} else {
												// Unset the items array and break out of both loops
												$items = array();
												$items[] = $childItem;
												break 2;
											}
										} else {
											$collectionItems[] = $childItem;
										}
									}
								} else {
									$collectionItems[] = $childItem;
								}
							}
						}
					}
					// Remove collection item
					$toRemove = $row['id'];
					if (array_key_exists($toRemove, $switchedItems)) {
						$toRemove = $switchedItems[$toRemove];
					}
					unset($items[$toRemove]);
				}
			}
			if (!empty($collectionItems)) {
				$items = array_merge($items, $collectionItems);
			}
			if (empty($items) && $limit == 1) {
				return false;
			}
			if ($format == self::FORMAT_NONE) {
				return $items;
			} else if ($format == self::FORMAT_STATUSES) {
				$statuses = array();
				foreach ($items as $item) {
					if ($item['share_type'] == self::SHARE_TYPE_LINK) {
						$statuses[$item[$column]]['link'] = true;
					} else if (!isset($statuses[$item[$column]])) {
						$statuses[$item[$column]]['link'] = false;
					}
					if ($itemType == 'file' || $itemType == 'folder') {
						$statuses[$item[$column]]['path'] = $item['path'];
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
	 * Put shared item into the database
	 * @param string Item type
	 * @param string Item source
	 * @param int SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string User or group the item is being shared with
	 * @param string User that is the owner of shared item
	 * @param int CRUDS permissions
	 * @param bool|array Parent folder target (optional)
	 * @param string token (optional)
	 * @param string name of the source item (optional)
	 * @return bool Returns true on success or false on failure
	 */
	private static function put($itemType, $itemSource, $shareType, $shareWith, $uidOwner,
		$permissions, $parentFolder = null, $token = null, $itemSourceName = null) {
		$backend = self::getBackend($itemType);

		// Check if this is a reshare
		if ($checkReshare = self::getItemSharedWithBySource($itemType, $itemSource, self::FORMAT_NONE, null, true)) {

			// Check if attempting to share back to owner
			if ($checkReshare['uid_owner'] == $shareWith && $shareType == self::SHARE_TYPE_USER) {
				$message = 'Sharing '.$itemSourceName.' failed, because the user '.$shareWith.' is the original sharer';
				\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
				throw new \Exception($message);
			}
			// Check if share permissions is granted
			if (self::isResharingAllowed() && (int)$checkReshare['permissions'] & PERMISSION_SHARE) {
				if (~(int)$checkReshare['permissions'] & $permissions) {
					$message = 'Sharing '.$itemSourceName
						.' failed, because the permissions exceed permissions granted to '.$uidOwner;
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				} else {
					// TODO Don't check if inside folder
					$parent = $checkReshare['id'];
					$itemSource = $checkReshare['item_source'];
					$fileSource = $checkReshare['file_source'];
					$suggestedItemTarget = $checkReshare['item_target'];
					$suggestedFileTarget = $checkReshare['file_target'];
					$filePath = $checkReshare['file_target'];
				}
			} else {
				$message = 'Sharing '.$itemSourceName.' failed, because resharing is not allowed';
				\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
				throw new \Exception($message);
			}
		} else {
			$parent = null;
			$suggestedItemTarget = null;
			$suggestedFileTarget = null;
			if (!$backend->isValidSource($itemSource, $uidOwner)) {
				$message = 'Sharing '.$itemSource.' failed, because the sharing backend for '
					.$itemType.' could not find its source';
				\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
				throw new \Exception($message);
			}
			$parent = null;
			if ($backend instanceof Share_Backend_File_Dependent) {
				$filePath = $backend->getFilePath($itemSource, $uidOwner);
				if ($itemType == 'file' || $itemType == 'folder') {
					$fileSource = $itemSource;
				} else {
					$meta = \OC\Files\Filesystem::getFileInfo($filePath);
					$fileSource = $meta['fileid'];
				}
				if ($fileSource == -1) {
					$message = 'Sharing '.$itemSource.' failed, because the file could not be found in the file cache';
					\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
					throw new \Exception($message);
				}
			} else {
				$filePath = null;
				$fileSource = null;
			}
		}
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*share` (`item_type`, `item_source`, `item_target`,'
			.' `parent`, `share_type`, `share_with`, `uid_owner`, `permissions`, `stime`, `file_source`,'
			.' `file_target`, `token`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
		// Share with a group
		if ($shareType == self::SHARE_TYPE_GROUP) {
			$groupItemTarget = self::generateTarget($itemType, $itemSource, $shareType, $shareWith['group'],
				$uidOwner, $suggestedItemTarget);
			$run = true;
			$error = '';
			\OC_Hook::emit('OCP\Share', 'pre_shared', array(
				'itemType' => $itemType,
				'itemSource' => $itemSource,
				'itemTarget' => $groupItemTarget,
				'shareType' => $shareType,
				'shareWith' => $shareWith['group'],
				'uidOwner' => $uidOwner,
				'permissions' => $permissions,
				'fileSource' => $fileSource,
				'token' => $token,
				'run' => &$run,
				'error' => &$error
			));

			if ($run === false) {
				throw new \Exception($error);
			}

			if (isset($fileSource)) {
				if ($parentFolder) {
					if ($parentFolder === true) {
						$groupFileTarget = self::generateTarget('file', $filePath, $shareType,
							$shareWith['group'], $uidOwner, $suggestedFileTarget);
						// Set group default file target for future use
						$parentFolders[0]['folder'] = $groupFileTarget;
					} else {
						// Get group default file target
						$groupFileTarget = $parentFolder[0]['folder'].$itemSource;
						$parent = $parentFolder[0]['id'];
					}
				} else {
					$groupFileTarget = self::generateTarget('file', $filePath, $shareType, $shareWith['group'],
						$uidOwner, $suggestedFileTarget);
				}
			} else {
				$groupFileTarget = null;
			}
			$query->execute(array($itemType, $itemSource, $groupItemTarget, $parent, $shareType,
				$shareWith['group'], $uidOwner, $permissions, time(), $fileSource, $groupFileTarget, $token));
			// Save this id, any extra rows for this group share will need to reference it
			$parent = \OC_DB::insertid('*PREFIX*share');
			// Loop through all users of this group in case we need to add an extra row
			foreach ($shareWith['users'] as $uid) {
				$itemTarget = self::generateTarget($itemType, $itemSource, self::SHARE_TYPE_USER, $uid,
					$uidOwner, $suggestedItemTarget, $parent);
				if (isset($fileSource)) {
					if ($parentFolder) {
						if ($parentFolder === true) {
							$fileTarget = self::generateTarget('file', $filePath, self::SHARE_TYPE_USER, $uid,
								$uidOwner, $suggestedFileTarget, $parent);
							if ($fileTarget != $groupFileTarget) {
								$parentFolders[$uid]['folder'] = $fileTarget;
							}
						} else if (isset($parentFolder[$uid])) {
							$fileTarget = $parentFolder[$uid]['folder'].$itemSource;
							$parent = $parentFolder[$uid]['id'];
						}
					} else {
						$fileTarget = self::generateTarget('file', $filePath, self::SHARE_TYPE_USER,
							$uid, $uidOwner, $suggestedFileTarget, $parent);
					}
				} else {
					$fileTarget = null;
				}
				// Insert an extra row for the group share if the item or file target is unique for this user
				if ($itemTarget != $groupItemTarget || (isset($fileSource) && $fileTarget != $groupFileTarget)) {
					$query->execute(array($itemType, $itemSource, $itemTarget, $parent,
						self::$shareTypeGroupUserUnique, $uid, $uidOwner, $permissions, time(),
							$fileSource, $fileTarget, $token));
					$id = \OC_DB::insertid('*PREFIX*share');
				}
			}
			\OC_Hook::emit('OCP\Share', 'post_shared', array(
				'itemType' => $itemType,
				'itemSource' => $itemSource,
				'itemTarget' => $groupItemTarget,
				'parent' => $parent,
				'shareType' => $shareType,
				'shareWith' => $shareWith['group'],
				'uidOwner' => $uidOwner,
				'permissions' => $permissions,
				'fileSource' => $fileSource,
				'fileTarget' => $groupFileTarget,
				'id' => $parent,
				'token' => $token
			));

			if ($parentFolder === true) {
				// Return parent folders to preserve file target paths for potential children
				return $parentFolders;
			}
		} else {
			$itemTarget = self::generateTarget($itemType, $itemSource, $shareType, $shareWith, $uidOwner,
				$suggestedItemTarget);
			$run = true;
			$error = '';
			\OC_Hook::emit('OCP\Share', 'pre_shared', array(
				'itemType' => $itemType,
				'itemSource' => $itemSource,
				'itemTarget' => $itemTarget,
				'shareType' => $shareType,
				'shareWith' => $shareWith,
				'uidOwner' => $uidOwner,
				'permissions' => $permissions,
				'fileSource' => $fileSource,
				'token' => $token,
				'run' => &$run,
				'error' => &$error
			));

			if ($run === false) {
				throw new \Exception($error);
			}

			if (isset($fileSource)) {
				if ($parentFolder) {
					if ($parentFolder === true) {
						$fileTarget = self::generateTarget('file', $filePath, $shareType, $shareWith,
							$uidOwner, $suggestedFileTarget);
						$parentFolders['folder'] = $fileTarget;
					} else {
						$fileTarget = $parentFolder['folder'].$itemSource;
						$parent = $parentFolder['id'];
					}
				} else {
					$fileTarget = self::generateTarget('file', $filePath, $shareType, $shareWith, $uidOwner,
						$suggestedFileTarget);
				}
			} else {
				$fileTarget = null;
			}
			$query->execute(array($itemType, $itemSource, $itemTarget, $parent, $shareType, $shareWith, $uidOwner,
				$permissions, time(), $fileSource, $fileTarget, $token));
			$id = \OC_DB::insertid('*PREFIX*share');
			\OC_Hook::emit('OCP\Share', 'post_shared', array(
				'itemType' => $itemType,
				'itemSource' => $itemSource,
				'itemTarget' => $itemTarget,
				'parent' => $parent,
				'shareType' => $shareType,
				'shareWith' => $shareWith,
				'uidOwner' => $uidOwner,
				'permissions' => $permissions,
				'fileSource' => $fileSource,
				'fileTarget' => $fileTarget,
				'id' => $id,
				'token' => $token
			));
			if ($parentFolder === true) {
				$parentFolders['id'] = $id;
				// Return parent folder to preserve file target paths for potential children
				return $parentFolders;
			}
		}
		return true;
	}

	/**
	 * Generate a unique target for the item
	 * @param string Item type
	 * @param string Item source
	 * @param int SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string User or group the item is being shared with
	 * @param string User that is the owner of shared item
	 * @param string The suggested target originating from a reshare (optional)
	 * @param int The id of the parent group share (optional)
	 * @return string Item target
	 */
	private static function generateTarget($itemType, $itemSource, $shareType, $shareWith, $uidOwner,
		$suggestedTarget = null, $groupParent = null) {
		$backend = self::getBackend($itemType);
		if ($shareType == self::SHARE_TYPE_LINK) {
			if (isset($suggestedTarget)) {
				return $suggestedTarget;
			}
			return $backend->generateTarget($itemSource, false);
		} else {
			if ($itemType == 'file' || $itemType == 'folder') {
				$column = 'file_target';
				$columnSource = 'file_source';
			} else {
				$column = 'item_target';
				$columnSource = 'item_source';
			}
			if ($shareType == self::SHARE_TYPE_USER) {
				// Share with is a user, so set share type to user and groups
				$shareType = self::$shareTypeUserAndGroups;
				$userAndGroups = array_merge(array($shareWith), \OC_Group::getUserGroups($shareWith));
			} else {
				$userAndGroups = false;
			}
			$exclude = null;
			// Backend has 3 opportunities to generate a unique target
			for ($i = 0; $i < 2; $i++) {
				// Check if suggested target exists first
				if ($i == 0 && isset($suggestedTarget)) {
					$target = $suggestedTarget;
				} else {
					if ($shareType == self::SHARE_TYPE_GROUP) {
						$target = $backend->generateTarget($itemSource, false, $exclude);
					} else {
						$target = $backend->generateTarget($itemSource, $shareWith, $exclude);
					}
					if (is_array($exclude) && in_array($target, $exclude)) {
						break;
					}
				}
				// Check if target already exists
				$checkTarget = self::getItems($itemType, $target, $shareType, $shareWith);
				if (!empty($checkTarget)) {
					foreach ($checkTarget as $item) {
						// Skip item if it is the group parent row
						if (isset($groupParent) && $item['id'] == $groupParent) {
							if (count($checkTarget) == 1) {
								return $target;
							} else {
								continue;
							}
						}
						if ($item['uid_owner'] == $uidOwner) {
							if ($itemType == 'file' || $itemType == 'folder') {
								$meta = \OC\Files\Filesystem::getFileInfo($itemSource);
								if ($item['file_source'] == $meta['fileid']) {
									return $target;
								}
							} else if ($item['item_source'] == $itemSource) {
								return $target;
							}
						}
					}
					if (!isset($exclude)) {
						$exclude = array();
					}
					// Find similar targets to improve backend's chances to generate a unqiue target
					if ($userAndGroups) {
						if ($column == 'file_target') {
							$checkTargets = \OC_DB::prepare('SELECT `'.$column.'` FROM `*PREFIX*share`'
								.' WHERE `item_type` IN (\'file\', \'folder\')'
								.' AND `share_type` IN (?,?,?)'
								.' AND `share_with` IN (\''.implode('\',\'', $userAndGroups).'\')');
							$result = $checkTargets->execute(array(self::SHARE_TYPE_USER, self::SHARE_TYPE_GROUP,
								self::$shareTypeGroupUserUnique));
						} else {
							$checkTargets = \OC_DB::prepare('SELECT `'.$column.'` FROM `*PREFIX*share`'
								.' WHERE `item_type` = ? AND `share_type` IN (?,?,?)'
								.' AND `share_with` IN (\''.implode('\',\'', $userAndGroups).'\')');
							$result = $checkTargets->execute(array($itemType, self::SHARE_TYPE_USER,
								self::SHARE_TYPE_GROUP, self::$shareTypeGroupUserUnique));
						}
					} else {
						if ($column == 'file_target') {
							$checkTargets = \OC_DB::prepare('SELECT `'.$column.'` FROM `*PREFIX*share`'
								.' WHERE `item_type` IN (\'file\', \'folder\')'
								.' AND `share_type` = ? AND `share_with` = ?');
							$result = $checkTargets->execute(array(self::SHARE_TYPE_GROUP, $shareWith));
						} else {
							$checkTargets = \OC_DB::prepare('SELECT `'.$column.'` FROM `*PREFIX*share`'
								.' WHERE `item_type` = ? AND `share_type` = ? AND `share_with` = ?');
							$result = $checkTargets->execute(array($itemType, self::SHARE_TYPE_GROUP, $shareWith));
						}
					}
					while ($row = $result->fetchRow()) {
						$exclude[] = $row[$column];
					}
				} else {
					return $target;
				}
			}
		}
		$message = 'Sharing backend registered for '.$itemType.' did not generate a unique target for '.$itemSource;
		\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
		throw new \Exception($message);
	}

	/**
	 * Delete all reshares of an item
	 * @param int Id of item to delete
	 * @param bool If true, exclude the parent from the delete (optional)
	 * @param string The user that the parent was shared with (optinal)
	 */
	private static function delete($parent, $excludeParent = false, $uidOwner = null) {
		$ids = array($parent);
		$parents = array($parent);
		while (!empty($parents)) {
			$parents = "'".implode("','", $parents)."'";
			// Check the owner on the first search of reshares, useful for
			// finding and deleting the reshares by a single user of a group share
			if (count($ids) == 1 && isset($uidOwner)) {
				$query = \OC_DB::prepare('SELECT `id`, `uid_owner`, `item_type`, `item_target`, `parent`'
					.' FROM `*PREFIX*share` WHERE `parent` IN ('.$parents.') AND `uid_owner` = ?');
				$result = $query->execute(array($uidOwner));
			} else {
				$query = \OC_DB::prepare('SELECT `id`, `item_type`, `item_target`, `parent`, `uid_owner`'
					.' FROM `*PREFIX*share` WHERE `parent` IN ('.$parents.')');
				$result = $query->execute();
			}
			// Reset parents array, only go through loop again if items are found
			$parents = array();
			while ($item = $result->fetchRow()) {
				// Search for a duplicate parent share, this occurs when an
				// item is shared to the same user through a group and user or the
				// same item is shared by different users
				$userAndGroups = array_merge(array($item['uid_owner']), \OC_Group::getUserGroups($item['uid_owner']));
				$query = \OC_DB::prepare('SELECT `id`, `permissions` FROM `*PREFIX*share`'
					.' WHERE `item_type` = ?'
					.' AND `item_target` = ?'
					.' AND `share_type` IN (?,?,?)'
					.' AND `share_with` IN (\''.implode('\',\'', $userAndGroups).'\')'
					.' AND `uid_owner` != ? AND `id` != ?');
				$duplicateParent = $query->execute(array($item['item_type'], $item['item_target'],
					self::SHARE_TYPE_USER, self::SHARE_TYPE_GROUP, self::$shareTypeGroupUserUnique,
					$item['uid_owner'], $item['parent']))->fetchRow();
				if ($duplicateParent) {
					// Change the parent to the other item id if share permission is granted
					if ($duplicateParent['permissions'] & PERMISSION_SHARE) {
						$query = \OC_DB::prepare('UPDATE `*PREFIX*share` SET `parent` = ? WHERE `id` = ?');
						$query->execute(array($duplicateParent['id'], $item['id']));
						continue;
					}
				}
				$ids[] = $item['id'];
				$parents[] = $item['id'];
			}
		}
		if ($excludeParent) {
			unset($ids[0]);
		}
		if (!empty($ids)) {
			$ids = "'".implode("','", $ids)."'";
			$query = \OC_DB::prepare('DELETE FROM `*PREFIX*share` WHERE `id` IN ('.$ids.')');
			$query->execute();
		}
	}

	/**
	 * Delete all shares with type SHARE_TYPE_LINK
	 */
	public static function removeAllLinkShares() {
		// Delete any link shares
		$query = \OC_DB::prepare('SELECT `id` FROM `*PREFIX*share` WHERE `share_type` = ?');
		$result = $query->execute(array(self::SHARE_TYPE_LINK));
		while ($item = $result->fetchRow()) {
			self::delete($item['id']);
		}
	}

	/**
	 * Hook Listeners
	 */

	/**
	 * Function that is called after a user is deleted. Cleans up the shares of that user.
	 * @param array arguments
	 */
	public static function post_deleteUser($arguments) {
		// Delete any items shared with the deleted user
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*share`'
			.' WHERE `share_with` = ? AND `share_type` = ? OR `share_type` = ?');
		$result = $query->execute(array($arguments['uid'], self::SHARE_TYPE_USER, self::$shareTypeGroupUserUnique));
		// Delete any items the deleted user shared
		$query = \OC_DB::prepare('SELECT `id` FROM `*PREFIX*share` WHERE `uid_owner` = ?');
		$result = $query->execute(array($arguments['uid']));
		while ($item = $result->fetchRow()) {
			self::delete($item['id']);
		}
	}

	/**
	 * Function that is called after a user is added to a group.
	 * TODO what does it do?
	 * @param array arguments
	 */
	public static function post_addToGroup($arguments) {
		// Find the group shares and check if the user needs a unique target
		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*share` WHERE `share_type` = ? AND `share_with` = ?');
		$result = $query->execute(array(self::SHARE_TYPE_GROUP, $arguments['gid']));
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*share` (`item_type`, `item_source`,'
			.' `item_target`, `parent`, `share_type`, `share_with`, `uid_owner`, `permissions`,'
			.' `stime`, `file_source`, `file_target`) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
		while ($item = $result->fetchRow()) {
			if ($item['item_type'] == 'file' || $item['item_type'] == 'file') {
				$itemTarget = null;
			} else {
				$itemTarget = self::generateTarget($item['item_type'], $item['item_source'], self::SHARE_TYPE_USER,
					$arguments['uid'], $item['uid_owner'], $item['item_target'], $item['id']);
			}
			if (isset($item['file_source'])) {
				$fileTarget = self::generateTarget($item['item_type'], $item['item_source'], self::SHARE_TYPE_USER,
					$arguments['uid'], $item['uid_owner'], $item['file_target'], $item['id']);
			} else {
				$fileTarget = null;
			}
			// Insert an extra row for the group share if the item or file target is unique for this user
			if ($itemTarget != $item['item_target'] || $fileTarget != $item['file_target']) {
				$query->execute(array($item['item_type'], $item['item_source'], $itemTarget, $item['id'],
					self::$shareTypeGroupUserUnique, $arguments['uid'], $item['uid_owner'], $item['permissions'],
					$item['stime'], $item['file_source'], $fileTarget));
				\OC_DB::insertid('*PREFIX*share');
			}
		}
	}

	/**
	 * Function that is called after a user is removed from a group. Shares are cleaned up.
	 * @param array arguments
	 */
	public static function post_removeFromGroup($arguments) {
		// TODO Don't call if user deleted?
		$sql = 'SELECT `id`, `share_type` FROM `*PREFIX*share`'
			.' WHERE (`share_type` = ? AND `share_with` = ?) OR (`share_type` = ? AND `share_with` = ?)';
		$result = \OC_DB::executeAudited($sql, array(self::SHARE_TYPE_GROUP, $arguments['gid'],
			self::$shareTypeGroupUserUnique, $arguments['uid']));
		while ($item = $result->fetchRow()) {
			if ($item['share_type'] == self::SHARE_TYPE_GROUP) {
				// Delete all reshares by this user of the group share
				self::delete($item['id'], true, $arguments['uid']);
			} else {
				self::delete($item['id']);
			}
		}
	}

	/**
	 * Function that is called after a group is removed. Cleans up the shares to that group.
	 * @param array arguments
	 */
	public static function post_deleteGroup($arguments) {
		$sql = 'SELECT `id` FROM `*PREFIX*share` WHERE `share_type` = ? AND `share_with` = ?';
		$result = \OC_DB::executeAudited($sql, array(self::SHARE_TYPE_GROUP, $arguments['gid']));
		while ($item = $result->fetchRow()) {
			self::delete($item['id']);
		}
	}

	/**
	 * In case a password protected link is not yet authenticated this function will return false
	 *
	 * @param array $linkItem
	 * @return bool
	 */
	public static function checkPasswordProtectedShare(array $linkItem) {
		if (!isset($linkItem['share_with'])) {
			return true;
		}
		if (!isset($linkItem['share_type'])) {
			return true;
		}
		if (!isset($linkItem['id'])) {
			return true;
		}

		if ($linkItem['share_type'] != \OCP\Share::SHARE_TYPE_LINK) {
			return true;
		}

		if ( \OC::$session->exists('public_link_authenticated')
			&& \OC::$session->get('public_link_authenticated') === $linkItem['id'] ) {
			return true;
		}

		return false;
	}
}

/**
 * Interface that apps must implement to share content.
 */
interface Share_Backend {

	/**
	 * Get the source of the item to be stored in the database
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
	 * Get a unique name of the item for the specified user
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
	 * Converts the shared item sources back into the item in the specified format
	 * @param array Shared items
	 * @param int Format
	 * @return TODO
	 *
	 * The items array is a 3-dimensional array with the item_source as the
	 * first key and the share id as the second key to an array with the share
	 * info.
	 *
	 * The key/value pairs included in the share info depend on the function originally called:
	 * If called by getItem(s)Shared: id, item_type, item, item_source,
	 * share_type, share_with, permissions, stime, file_source
	 *
	 * If called by getItem(s)SharedWith: id, item_type, item, item_source,
	 * item_target, share_type, share_with, permissions, stime, file_source,
	 * file_target
	 *
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
	 * Get the file path of the item
	 * @param string Item source
	 * @param string User that is the owner of shared item
	 */
	public function getFilePath($itemSource, $uidOwner);

}

/**
 * Interface for collections of of items implemented by another share backend.
 * Extends the Share_Backend interface.
 */
interface Share_Backend_Collection extends Share_Backend {

	/**
	 * Get the sources of the children of the item
	 * @param string Item source
	 * @return array Returns an array of children each inside an array with the keys: source, target, and file_path if applicable
	 */
	public function getChildren($itemSource);

}
