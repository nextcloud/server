<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Michael Kuhn <suraia@ikkoku.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
 * @since 5.0.0
 */
class Share extends \OC\Share\Constants {

	/**
	 * Register a sharing backend class that implements OCP\Share_Backend for an item type
	 * @param string $itemType Item type
	 * @param string $class Backend class
	 * @param string $collectionOf (optional) Depends on item type
	 * @param array $supportedFileExtensions (optional) List of supported file extensions if this item type depends on files
	 * @return boolean true if backend is registered or false if error
	 * @since 5.0.0
	 */
	public static function registerBackend($itemType, $class, $collectionOf = null, $supportedFileExtensions = null) {
		return \OC\Share\Share::registerBackend($itemType, $class, $collectionOf, $supportedFileExtensions);
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
	 * @since 7.0.0
	 */
	public static function getItemsSharedWithUser($itemType, $user, $format = self::FORMAT_NONE,
		$parameters = null, $limit = -1, $includeCollections = false) {

		return \OC\Share\Share::getItemsSharedWithUser($itemType, $user, $format, $parameters, $limit, $includeCollections);
	}

	/**
	 * Get the item of item type shared with a given user by source
	 * @param string $itemType
	 * @param string $itemSource
	 * @param string $user User to whom the item was shared
	 * @param string $owner Owner of the share
	 * @return array Return list of items with file_target, permissions and expiration
	 * @since 6.0.0 - parameter $owner was added in 8.0.0
	 */
	public static function getItemSharedWithUser($itemType, $itemSource, $user, $owner = null) {
		return \OC\Share\Share::getItemSharedWithUser($itemType, $itemSource, $user, $owner);
	}

	/**
	 * Get the shared item of item type owned by the current user
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $format (optional) Format type must be defined by the backend
	 * @param mixed $parameters
	 * @param bool $includeCollections
	 * @return mixed Return depends on format
	 * @since 5.0.0
	 */
	public static function getItemShared($itemType, $itemSource, $format = self::FORMAT_NONE,
	                                     $parameters = null, $includeCollections = false) {

		return \OC\Share\Share::getItemShared($itemType, $itemSource, $format, $parameters, $includeCollections);
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
	 * @param bool $passwordChanged
	 * @return bool|string Returns true on success or false on failure, Returns token on success for links
	 * @throws \OC\HintException when the share type is remote and the shareWith is invalid
	 * @throws \Exception
	 * @since 5.0.0 - parameter $itemSourceName was added in 6.0.0, parameter $expirationDate was added in 7.0.0, parameter $passwordChanged added in 9.0.0
	 */
	public static function shareItem($itemType, $itemSource, $shareType, $shareWith, $permissions, $itemSourceName = null, \DateTime $expirationDate = null, $passwordChanged = null) {
		return \OC\Share\Share::shareItem($itemType, $itemSource, $shareType, $shareWith, $permissions, $itemSourceName, $expirationDate, $passwordChanged);
	}

	/**
	 * Unshare an item from a user, group, or delete a private link
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $shareType SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string $shareWith User or group the item is being shared with
	 * @param string $owner owner of the share, if null the current user is used
	 * @return boolean true on success or false on failure
	 * @since 5.0.0 - parameter $owner was added in 8.0.0
	 */
	public static function unshare($itemType, $itemSource, $shareType, $shareWith, $owner = null) {
		return \OC\Share\Share::unshare($itemType, $itemSource, $shareType, $shareWith, $owner);
	}

	/**
	 * Set expiration date for a share
	 * @param string $itemType
	 * @param string $itemSource
	 * @param string $date expiration date
	 * @param int $shareTime timestamp from when the file was shared
	 * @return boolean
	 * @since 5.0.0 - parameter $shareTime was added in 8.0.0
	 */
	public static function setExpirationDate($itemType, $itemSource, $date, $shareTime = null) {
		return \OC\Share\Share::setExpirationDate($itemType, $itemSource, $date, $shareTime);
	}

	/**
	 * Get the backend class for the specified item type
	 * @param string $itemType
	 * @return Share_Backend
	 * @since 5.0.0
	 */
	public static function getBackend($itemType) {
		return \OC\Share\Share::getBackend($itemType);
	}
}
