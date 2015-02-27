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
	 * @param string $itemType Item type
	 * @param string $class Backend class
	 * @param string $collectionOf (optional) Depends on item type
	 * @param array $supportedFileExtensions (optional) List of supported file extensions if this item type depends on files
	 * @return boolean true if backend is registered or false if error
	 */
	public static function registerBackend($itemType, $class, $collectionOf = null, $supportedFileExtensions = null) {
		return \OC\Share\Share::registerBackend($itemType, $class, $collectionOf, $supportedFileExtensions);
	}

	/**
	 * Check if the Share API is enabled
	 * @return boolean true if enabled or false
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
	 * @param string $itemType
	 * @param int $format (optional) Format type must be defined by the backend
	 * @param mixed $parameters (optional)
	 * @param int $limit Number of items to return (optional) Returns all by default
	 * @param bool $includeCollections (optional)
	 * @return mixed Return depends on format
	 */
	public static function getItemsSharedWith($itemType, $format = self::FORMAT_NONE,
		$parameters = null, $limit = -1, $includeCollections = false) {

		return \OC\Share\Share::getItemsSharedWith($itemType, $format, $parameters, $limit, $includeCollections);
	}

	/**
	 * Get the items of item type shared with a user
	 * @param string $itemType
	 * @param string $user for which user we want the shares
	 * @param int $format (optional) Format type must be defined by the backend
	 * @param mixed $parameters (optional)
	 * @param int $limit Number of items to return (optional) Returns all by default
	 * @param bool $includeCollections (optional)
	 * @return mixed Return depends on format
	 */
	public static function getItemsSharedWithUser($itemType, $user, $format = self::FORMAT_NONE,
		$parameters = null, $limit = -1, $includeCollections = false) {

		return \OC\Share\Share::getItemsSharedWithUser($itemType, $user, $format, $parameters, $limit, $includeCollections);
	}

	/**
	 * Get the item of item type shared with the current user
	 * @param string $itemType
	 * @param string $itemTarget
	 * @param int $format (optional) Format type must be defined by the backend
	 * @param mixed $parameters (optional)
	 * @param bool $includeCollections (optional)
	 * @return mixed Return depends on format
	 */
	public static function getItemSharedWith($itemType, $itemTarget, $format = self::FORMAT_NONE,
		$parameters = null, $includeCollections = false) {

		return \OC\Share\Share::getItemSharedWith($itemType, $itemTarget, $format, $parameters, $includeCollections);
	}

	/**
	 * Get the item of item type shared with a given user by source
	 * @param string $itemType
	 * @param string $itemSource
	 * @param string $user User to whom the item was shared
	 * @param string $owner Owner of the share
	 * @return array Return list of items with file_target, permissions and expiration
	 */
	public static function getItemSharedWithUser($itemType, $itemSource, $user, $owner = null) {
		return \OC\Share\Share::getItemSharedWithUser($itemType, $itemSource, $user, $owner);
	}

	/**
	 * Get the item of item type shared with the current user by source
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $format (optional) Format type must be defined by the backend
	 * @param mixed $parameters
	 * @param bool $includeCollections
	 * @return array
	 */
	public static function getItemSharedWithBySource($itemType, $itemSource, $format = self::FORMAT_NONE,
		$parameters = null, $includeCollections = false) {
		return \OC\Share\Share::getItemSharedWithBySource($itemType, $itemSource, $format, $parameters, $includeCollections);
	}

	/**
	 * Get the item of item type shared by a link
	 * @param string $itemType
	 * @param string $itemSource
	 * @param string $uidOwner Owner of link
	 * @return Item
	 */
	public static function getItemSharedWithByLink($itemType, $itemSource, $uidOwner) {
		return \OC\Share\Share::getItemSharedWithByLink($itemType, $itemSource, $uidOwner);
	}

	/**
	 * Based on the given token the share information will be returned - password protected shares will be verified
	 * @param string $token
	 * @return array|bool false will be returned in case the token is unknown or unauthorized
	 */
	public static function getShareByToken($token, $checkPasswordProtection = true) {
		return \OC\Share\Share::getShareByToken($token, $checkPasswordProtection);
	}

	/**
	 * resolves reshares down to the last real share
	 * @param array $linkItem
	 * @return array file owner
	 */
	public static function resolveReShare($linkItem) {
		return \OC\Share\Share::resolveReShare($linkItem);
	}


	/**
	 * Get the shared items of item type owned by the current user
	 * @param string $itemType
	 * @param int $format (optional) Format type must be defined by the backend
	 * @param mixed $parameters
	 * @param int $limit Number of items to return (optional) Returns all by default
	 * @param bool $includeCollections
	 * @return mixed Return depends on format
	 */
	public static function getItemsShared($itemType, $format = self::FORMAT_NONE, $parameters = null,
		$limit = -1, $includeCollections = false) {

		return \OC\Share\Share::getItemsShared($itemType, $format, $parameters, $limit, $includeCollections);
	}

	/**
	 * Get the shared item of item type owned by the current user
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $format (optional) Format type must be defined by the backend
	 * @param mixed $parameters
	 * @param bool $includeCollections
	 * @return mixed Return depends on format
	 */
	public static function getItemShared($itemType, $itemSource, $format = self::FORMAT_NONE,
	                                     $parameters = null, $includeCollections = false) {

		return \OC\Share\Share::getItemShared($itemType, $itemSource, $format, $parameters, $includeCollections);
	}

	/**
	 * Get all users an item is shared with
	 * @param string $itemType
	 * @param string $itemSource
	 * @param string $uidOwner
	 * @param bool $includeCollections
	 * @param bool $checkExpireDate
	 * @return array Return array of users
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
	 * @param string $itemSourceName
	 * @param \DateTime $expirationDate
	 * @return bool|string Returns true on success or false on failure, Returns token on success for links
	 * @throws \Exception
	 */
	public static function shareItem($itemType, $itemSource, $shareType, $shareWith, $permissions, $itemSourceName = null, \DateTime $expirationDate = null) {
		return \OC\Share\Share::shareItem($itemType, $itemSource, $shareType, $shareWith, $permissions, $itemSourceName, $expirationDate);
	}

	/**
	 * Unshare an item from a user, group, or delete a private link
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $shareType SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string $shareWith User or group the item is being shared with
	 * @param string $owner owner of the share, if null the current user is used
	 * @return boolean true on success or false on failure
	 */
	public static function unshare($itemType, $itemSource, $shareType, $shareWith, $owner = null) {
		return \OC\Share\Share::unshare($itemType, $itemSource, $shareType, $shareWith, $owner);
	}

	/**
	 * Unshare an item from all users, groups, and remove all links
	 * @param string $itemType
	 * @param string $itemSource
	 * @return boolean true on success or false on failure
	 */
	public static function unshareAll($itemType, $itemSource) {
		return \OC\Share\Share::unshareAll($itemType, $itemSource);
	}

	/**
	 * Unshare an item shared with the current user
	 * @param string $itemType
	 * @param string $itemTarget
	 * @return boolean true on success or false on failure
	 *
	 * Unsharing from self is not allowed for items inside collections
	 */
	public static function unshareFromSelf($itemType, $itemOrigin, $originIsSource = false) {
		return \OC\Share\Share::unshareFromSelf($itemType, $itemOrigin, $originIsSource);
	}

	/**
	 * sent status if users got informed by mail about share
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $shareType SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string $recipient with whom was the item shared
	 * @param bool $status
	 */
	public static function setSendMailStatus($itemType, $itemSource, $shareType, $recipient, $status) {
		return \OC\Share\Share::setSendMailStatus($itemType, $itemSource, $shareType, $recipient, $status);
	}

	/**
	 * Set the permissions of an item for a specific user or group
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $shareType SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string $shareWith User or group the item is being shared with
	 * @param int $permissions CRUDS permissions
	 * @return boolean true on success or false on failure
	 */
	public static function setPermissions($itemType, $itemSource, $shareType, $shareWith, $permissions) {
		return \OC\Share\Share::setPermissions($itemType, $itemSource, $shareType, $shareWith, $permissions);
	}

	/**
	 * Set expiration date for a share
	 * @param string $itemType
	 * @param string $itemSource
	 * @param string $date expiration date
	 * @param int $shareTime timestamp from when the file was shared
	 * @return boolean
	 */
	public static function setExpirationDate($itemType, $itemSource, $date, $shareTime = null) {
		return \OC\Share\Share::setExpirationDate($itemType, $itemSource, $date, $shareTime);
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

	/**
	 * Check if resharing is allowed
	 *
	 * @return boolean true if allowed or false
	 */
	public static function isResharingAllowed() {
		return \OC\Share\Share::isResharingAllowed();
	}
}
