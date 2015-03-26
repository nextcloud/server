<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Ross Nicoll <jrn@jrn.me.uk>
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
namespace OC\Files\Storage;

/**
* Uses phpseclib's Net_SFTP class and the Net_SFTP_Stream stream wrapper to
* provide access to SFTP servers.
*/
class SFTP_Key extends \OC\Files\Storage\SFTP {
	private $publicKey;
	private $privateKey;

	public function __construct($params) {
		parent::__construct($params);
		$this->publicKey = $params['public_key'];
		$this->privateKey = $params['private_key'];
	}

	/**
	 * Returns the connection.
	 *
	 * @return \Net_SFTP connected client instance
	 * @throws \Exception when the connection failed
	 */
	public function getConnection() {
		if (!is_null($this->client)) {
			return $this->client;
		}

		$hostKeys = $this->readHostKeys();
		$this->client = new \Net_SFTP($this->getHost());

		// The SSH Host Key MUST be verified before login().
		$currentHostKey = $this->client->getServerPublicHostKey();
		if (array_key_exists($this->getHost(), $hostKeys)) {
			if ($hostKeys[$this->getHost()] !== $currentHostKey) {
				throw new \Exception('Host public key does not match known key');
			}
		} else {
			$hostKeys[$this->getHost()] = $currentHostKey;
			$this->writeHostKeys($hostKeys);
		}

		$key = $this->getPrivateKey();
		if (is_null($key)) {
			throw new \Exception('Secret key could not be loaded');
		}
		if (!$this->client->login($this->getUser(), $key)) {
			throw new \Exception('Login failed');
		}
		return $this->client;
	}

	/**
	 * Returns the private key to be used for authentication to the remote server.
	 *
	 * @return \Crypt_RSA instance or null in case of a failure to load the key.
	 */
	private function getPrivateKey() {
		$key = new \Crypt_RSA();
		$key->setPassword(\OC::$server->getConfig()->getSystemValue('secret', ''));
		if (!$key->loadKey($this->privateKey)) {
			// Should this exception rather than return null?
			return null;
		}
		return $key;
	}

	/**
	 * Throws an exception if the provided host name/address is invalid (cannot be resolved
	 * and is not an IPv4 address).
	 *
	 * @return true; never returns in case of a problem, this return value is used just to
	 * make unit tests happy.
	 */
	public function assertHostAddressValid($hostname) {
		// TODO: Should handle IPv6 addresses too
		if (!preg_match('/^\d+\.\d+\.\d+\.\d+$/', $hostname) && gethostbyname($hostname) === $hostname) {
			// Hostname is not an IPv4 address and cannot be resolved via DNS
			throw new \InvalidArgumentException('Cannot resolve hostname.');
		}
		return true;
	}

	/**
	 * Throws an exception if the provided port number is invalid (cannot be resolved
	 * and is not an IPv4 address).
	 *
	 * @return true; never returns in case of a problem, this return value is used just to
	 * make unit tests happy.
	 */
	public function assertPortNumberValid($port) {
		if (!preg_match('/^\d+$/', $port)) {
			throw new \InvalidArgumentException('Port number must be a number.');
		}
		if ($port < 0 || $port > 65535) {
			throw new \InvalidArgumentException('Port number must be between 0 and 65535 inclusive.');
		}
		return true;
	}

	/**
	 * Replaces anything that's not an alphanumeric character or "." in a hostname
	 * with "_", to make it safe for use as part of a file name.
	 */
	protected function sanitizeHostName($name) {
		return preg_replace('/[^\d\w\._]/', '_', $name);
	}

	/**
	 * Replaces anything that's not an alphanumeric character or "_" in a username
	 * with "_", to make it safe for use as part of a file name.
	 */
	protected function sanitizeUserName($name) {
		return preg_replace('/[^\d\w_]/', '_', $name);
	}

	public function test() {
		if (empty($this->getHost())) {
			\OC::$server->getLogger()->warning('Hostname has not been specified');
			return false;
		}
		if (empty($this->getUser())) {
			\OC::$server->getLogger()->warning('Username has not been specified');
			return false;
		}
		if (!isset($this->privateKey)) {
			\OC::$server->getLogger()->warning('Private key was missing from the request');
			return false;
		}

		// Sanity check the host
		$hostParts = explode(':', $this->getHost());
		try {
			if (count($hostParts) == 1) {
				$hostname = $hostParts[0];
				$this->assertHostAddressValid($hostname);
			} else if (count($hostParts) == 2) {
				$hostname = $hostParts[0];
				$this->assertHostAddressValid($hostname);
				$this->assertPortNumberValid($hostParts[1]);
			} else {
				throw new \Exception('Host connection string is invalid.');
			}
		} catch(\Exception $e) {
			\OC::$server->getLogger()->warning($e->getMessage());
			return false;
		}

		// Validate the key
		$key = $this->getPrivateKey();
		if (is_null($key)) {
			\OC::$server->getLogger()->warning('Secret key could not be loaded');
			return false;
		}

		try {
			if ($this->getConnection()->nlist() === false) {
				return false;
			}
		} catch(\Exception $e) {
			// We should be throwing a more specific error, so we're not just catching
			// Exception here
			\OC::$server->getLogger()->warning($e->getMessage());
			return false;
		}

		// Save the key somewhere it can easily be extracted later
		if (\OC::$server->getUserSession()->getUser()) {
			$view = new \OC\Files\View('/'.\OC::$server->getUserSession()->getUser()->getUId().'/files_external/sftp_keys');
			if (!$view->is_dir('')) {
				if (!$view->mkdir('')) {
					\OC::$server->getLogger()->warning('Could not create secret key directory.');
					return false;
				}
			}
			$key_filename = $this->sanitizeUserName($this->getUser()).'@'.$this->sanitizeHostName($hostname).'.pub';
			$key_file = $view->fopen($key_filename, "w");
			if ($key_file) {
				fwrite($key_file, $this->publicKey);
				fclose($key_file);
			} else {
				\OC::$server->getLogger()->warning('Could not write secret key file.');
			}
		}

		return true;
	}
}
