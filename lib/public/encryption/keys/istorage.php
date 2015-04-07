<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
	 * @param boolean
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

	/**
	 * copy keys if a file was renamed
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function renameKeys($source, $target);

	/**
	 * move keys if a file was renamed
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function copyKeys($source, $target);

}
