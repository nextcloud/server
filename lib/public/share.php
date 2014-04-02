<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle, Michael Gapczynski
 * @copyright 2012 Michael Gapczynski <mtgap@owncloud.com>
 *            2014 Bjoern Schiessle <schiessle@owncloud.com>
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
class Share extends \OC\Share\Constants {

	/**
	 * Register a sharing backend class that implements OCP\Share_Backend for an item type
	 * @param string Item type
	 * @param string Backend class
	 * @param string (optional) Depends on item type
	 * @param array (optional) List of supported file extensions if this item type depends on files
	 * @return Returns true if backend is registered or false if error
	 */
	public static function registerBackend($itemType, $class, $collectionOf = null, $supportedFileExtensions = null) {
		return \OC\Share\Share::registerBackend($itemType, $class, $collectionOf, $supportedFileExtensions);
	}

	/**
	 * Check if the Share API is enabled
	 * @return Returns true if enabled or false
	 *
	 * The Share API is enabled by default if not configured
	 */
	public static function isEnabled() {
		return \OC\Share\Share::isEnabled();
	}

	/**
	 * Find which users can access a shared item
	 * @param string $path to the file
	 * @param string $ownerUser owner of the file
	 * @param bool $includeOwner include owner to the list of users with access to the file
	 * @param bool $returnUserPaths Return an array with the user => path map
	 * @return array
	 * @note $path needs to be relative to user data dir, e.g. 'file.txt'
	 *       not '/admin/data/file.txt'
	 */
	public static function getUsersSharingFile($path, $ownerUser, $includeOwner = false, $returnUserPaths = false) {
		return \OC\Share\Share::getUsersSharingFile($path, $ownerUser, $includeOwner, $returnUserPaths);
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

		return \OC\Share\Share::getItemsSharedWith($itemType, $format, $parameters, $limit, $includeCollections);
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

		return \OC\Share\Share::getItemSharedWith($itemType, $itemTarget, $format, $parameters, $includeCollections);
	}

	/**
	 * Get the item of item type shared with a given user by source
	 * @param string $itemType
	 * @param string $itemSource
	 * @param string $user User user to whom the item was shared
	 * @return array Return list of items with file_target, permissions and expiration
	 */
	public static function getItemSharedWithUser($itemType, $itemSource, $user) {
		return \OC\Share\Share::getItemSharedWithUser($itemType, $itemSource, $user);
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
		return \OC\Share\Share::getItemSharedWithBySource($itemType, $itemSource, $format, $parameters, $includeCollections);
	}

	/**
	 * Get the item of item type shared by a link
	 * @param string Item type
	 * @param string Item source
	 * @param string Owner of link
	 * @return Item
	 */
	public static function getItemSharedWithByLink($itemType, $itemSource, $uidOwner) {
		return \OC\Share\Share::getItemSharedWithByLink($itemType, $itemSource, $uidOwner);
	}

	/**
	 * Based on the given token the share information will be returned - password protected shares will be verified
	 * @param string $token
	 * @return array | bool false will be returned in case the token is unknown or unauthorized
	 */
	public static function getShareByToken($token, $checkPasswordProtection = true) {
		return \OC\Share\Share::getShareByToken($token, $checkPasswordProtection);
	}

	/**
	 * resolves reshares down to the last real share
	 * @param $linkItem
	 * @return $fileOwner
	 */
	public static function resolveReShare($linkItem) {
		return \OC\Share\Share::resolveReShare($linkItem);
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

		return \OC\Share\Share::getItemsShared($itemType, $format, $parameters, $limit, $includeCollections);
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

		return \OC\Share\Share::getItemShared($itemType, $itemSource, $format, $parameters, $includeCollections);
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
		return \OC\Share\Share::getUsersItemShared($itemType, $itemSource, $uidOwner, $includeCollections, $checkExpireDate);
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
		return \OC\Share\Share::shareItem($itemType, $itemSource, $shareType, $shareWith, $permissions, $itemSourceName);
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
		return \OC\Share\Share::unshare($itemType, $itemSource, $shareType, $shareWith);
	}

	/**
	 * Unshare an item from all users, groups, and remove all links
	 * @param string Item type
	 * @param string Item source
	 * @return Returns true on success or false on failure
	 */
	public static function unshareAll($itemType, $itemSource) {
		return \OC\Share\Share::unshareAll($itemType, $itemSource);
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
		return \OC\Share\Share::unshareFromSelf($itemType, $itemTarget);
	}
	/**
	 * sent status if users got informed by mail about share
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $shareType SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param bool $status
	 */
	public static function setSendMailStatus($itemType, $itemSource, $shareType, $status) {
		return \OC\Share\Share::setSendMailStatus($itemType, $itemSource, $shareType, $status);
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
		return \OC\Share\Share::setPermissions($itemType, $itemSource, $shareType, $shareWith, $permissions);
	}

	/**
	 * Set expiration date for a share
	 * @param string $itemType
	 * @param string $itemSource
	 * @param string $date expiration date
	 * @return Share_Backend
	 */
	public static function setExpirationDate($itemType, $itemSource, $date) {
		return \OC\Share\Share::setExpirationDate($itemType, $itemSource, $date);
	}

	/**
	 * Get the backend class for the specified item type
	 * @param string $itemType
	 * @return Share_Backend
	 */
	public static function getBackend($itemType) {
		return \OC\Share\Share::getBackend($itemType);
	}

	/**
	 * Delete all shares with type SHARE_TYPE_LINK
	 */
	public static function removeAllLinkShares() {
		return \OC\Share\Share::removeAllLinkShares();
	}

	/**
	 * In case a password protected link is not yet authenticated this function will return false
	 *
	 * @param array $linkItem
	 * @return bool
	 */
	public static function checkPasswordProtectedShare(array $linkItem) {
		return \OC\Share\Share::checkPasswordProtectedShare($linkItem);
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
