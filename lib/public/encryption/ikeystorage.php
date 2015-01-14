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

namespace OCP\Encryption;

interface IKeyStorage {

	/**
	 * get user specific key
	 *
	 * @param string $uid ID if the user for whom we want the key
	 * @param string $keyid id of the key
	 *
	 * @return mixed key
	 */
	public function getUserKey($uid, $keyid);

	/**
	 * get file specific key
	 *
	 * @param string $path path to file
	 * @param string $keyid id of the key
	 *
	 * @return mixed key
	 */
	public function getFileKey($path, $keyid);

	/**
	 * get system-wide encryption keys not related to a specific user,
	 * e.g something like a key for public link shares
	 *
	 * @param string $keyid id of the key
	 *
	 * @return mixed key
	 */
	public function getSystemUserKey($uid, $keyid);

	/**
	 * set user specific key
	 *
	 * @param string $uid ID if the user for whom we want the key
	 * @param string $keyid id of the key
	 * @param mixed $key
	 */
	public function setUserKey($uid, $keyid, $key);

	/**
	 * set file specific key
	 *
	 * @param string $path path to file
	 * @param string $keyid id of the key
	 * @param mixed $key
	 */
	public function setFileKey($path, $keyid, $key);

	/**
	 * set system-wide encryption keys not related to a specific user,
	 * e.g something like a key for public link shares
	 *
	 * @param string $keyid id of the key
	 * @param mixed $key
	 *
	 * @return mixed key
	 */
	public function setSystemUserKey($uid, $keyid, $key);

}
