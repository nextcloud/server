<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
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

namespace OC\Session;


use OCP\ISession;
use OCP\Security\ICrypto;

class CryptoSessionData implements \ArrayAccess, ISession {
	/** @var ISession */
	protected $session;

	/** @var \OCP\Security\ICrypto */
	protected $crypto;

	/** @var string */
	protected $passphrase;

	/**
	 * @param ISession $session
	 * @param ICrypto $crypto
	 * @param string $passphrase
	 */
	public function __construct(ISession $session, ICrypto $crypto, $passphrase) {
		$this->crypto = $crypto;
		$this->session = $session;
		$this->passphrase = $passphrase;
	}

	/**
	 * Set a value in the session
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value) {
		$encryptedValue = $this->crypto->encrypt($value, $this->passphrase);
		$this->session->set($key, $encryptedValue);
	}

	/**
	 * Get a value from the session
	 *
	 * @param string $key
	 * @return mixed should return null if $key does not exist
	 * @throws \Exception when the data could not be decrypted
	 */
	public function get($key) {
		$encryptedValue = $this->session->get($key);
		if ($encryptedValue === null) {
			return null;
		}

		$value = $this->crypto->decrypt($encryptedValue, $this->passphrase);
		return $value;
	}

	/**
	 * Check if a named key exists in the session
	 *
	 * @param string $key
	 * @return bool
	 */
	public function exists($key) {
		return $this->session->exists($key);
	}

	/**
	 * Remove a $key/$value pair from the session
	 *
	 * @param string $key
	 */
	public function remove($key) {
		$this->session->remove($key);
	}

	/**
	 * Reset and recreate the session
	 */
	public function clear() {
		$this->session->clear();
	}

	/**
	 * Close the session and release the lock
	 */
	public function close() {
		$this->session->close();
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return $this->exists($offset);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset) {
		$this->remove($offset);
	}
}
