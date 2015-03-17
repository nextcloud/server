<?php

/**
 * ownCloud
 *
 * @copyright (C) 2015 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
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

namespace OCP\Encryption\Keys;

interface IStorage {

	/**
	 * get user specific key
	 *
	 * @param string $uid ID if the user for whom we want the key
	 * @param string $keyId id of the key
	 *
	 * @return mixed key
	 */
	public function getUserKey($uid, $keyId);

	/**
	 * get file specific key
	 *
	 * @param string $path path to file
	 * @param string $keyId id of the key
	 *
	 * @return mixed key
	 */
	public function getFileKey($path, $keyId);

	/**
	 * get system-wide encryption keys not related to a specific user,
	 * e.g something like a key for public link shares
	 *
	 * @param string $keyId id of the key
	 *
	 * @return mixed key
	 */
	public function getSystemUserKey($keyId);

	/**
	 * set user specific key
	 *
	 * @param string $uid ID if the user for whom we want the key
	 * @param string $keyId id of the key
	 * @param mixed $key
	 */
	public function setUserKey($uid, $keyId, $key);

	/**
	 * set file specific key
	 *
	 * @param string $path path to file
	 * @param string $keyId id of the key
	 * @param mixed $key
	 */
	public function setFileKey($path, $keyId, $key);

	/**
	 * set system-wide encryption keys not related to a specific user,
	 * e.g something like a key for public link shares
	 *
	 * @param string $keyId id of the key
	 * @param mixed $key
	 *
	 * @return mixed key
	 */
	public function setSystemUserKey($keyId, $key);

	/**
	 * delete user specific key
	 *
	 * @param string $uid ID if the user for whom we want to delete the key
	 * @param string $keyId id of the key
	 *
	 * @return boolean
	 */
	public function deleteUserKey($uid, $keyId);

	/**
	 * delete file specific key
	 *
	 * @param string $path path to file
	 * @param string $keyId id of the key
	 *
	 * @return boolean
	 */
	public function deleteFileKey($path, $keyId);

	/**
	 * delete all file keys for a given file
	 *
	 * @param string $path to the file
	 * @return boolean
	 */
	public function deleteAllFileKeys($path);

	/**
	 * delete system-wide encryption keys not related to a specific user,
	 * e.g something like a key for public link shares
	 *
	 * @param string $keyId id of the key
	 *
	 * @return boolean
	 */
	public function deleteSystemUserKey($keyId);

}
