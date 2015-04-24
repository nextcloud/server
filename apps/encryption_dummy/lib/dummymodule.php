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

namespace OCA\Encryption_Dummy;

use OCP\Encryption\IEncryptionModule;

class DummyModule implements IEncryptionModule {

	/** @var boolean */
	protected $isWriteOperation;

	/**
	 * @return string defining the technical unique id
	 */
	public function getId() {
		return "OC_DUMMY_MODULE";
	}

	/**
	 * In comparison to getKey() this function returns a human readable (maybe translated) name
	 *
	 * @return string
	 */
	public function getDisplayName() {
		return "Dummy Encryption Module";
	}

	/**
	 * start receiving chunks from a file. This is the place where you can
	 * perform some initial step before starting encrypting/decrypting the
	 * chunks
	 *
	 * @param string $path to the file
	 * @param string $user who read/write the file (null for public access)
	 * @param string $mode php stream open mode
	 * @param array $header contains the header data read from the file
	 * @param array $accessList who has access to the file contains the key 'users' and 'public'
	 *
	 * @return array $header contain data as key-value pairs which should be
	 *                       written to the header, in case of a write operation
	 *                       or if no additional data is needed return a empty array
	 */
	public function begin($path, $user, $mode, array $header, array $accessList) {
		return array();
	}

	/**
	 * last chunk received. This is the place where you can perform some final
	 * operation and return some remaining data if something is left in your
	 * buffer.
	 *
	 * @param string $path to the file
	 * @return string remained data which should be written to the file in case
	 *                of a write operation
	 */
	public function end($path) {

		if ($this->isWriteOperation) {
			$storage = \OC::$server->getEncryptionKeyStorage();
			$storage->setFileKey($path, 'fileKey', 'foo', $this->getId());
		}
		return '';
	}

	/**
	 * encrypt data
	 *
	 * @param string $data you want to encrypt
	 * @return mixed encrypted data
	 */
	public function encrypt($data) {
		$this->isWriteOperation = true;
		return $data;
	}

	/**
	 * decrypt data
	 *
	 * @param string $data you want to decrypt
	 * @param string $user decrypt as user (null for public access)
	 * @return mixed decrypted data
	 */
	public function decrypt($data) {
		$this->isWriteOperation=false;
		return $data;
	}

	/**
	 * should the file be encrypted or not
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function shouldEncrypt($path) {
		if (strpos($path, '/'.  \OCP\User::getUser() . '/files/') === 0) {
			return true;
		}

		return false;
	}

	public function getUnencryptedBlockSize() {
		return 6126;
	}

	/**
	 * update encrypted file, e.g. give additional users access to the file
	 *
	 * @param string $path path to the file which should be updated
	 * @param string $uid of the user who performs the operation
	 * @param array $accessList who has access to the file contains the key 'users' and 'public'
	 * @return boolean
	 */
	public function update($path, $uid, array $accessList) {
		return true;
	}
}
