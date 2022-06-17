<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Memcache;

use OCP\HintException;
use OCP\IMemcache;

class Memcached extends Cache implements IMemcache {
	use CASTrait;

	/**
	 * @var \Memcached $cache
	 */
	private static $cache = null;

	use CADTrait;

	public function __construct($prefix = '') {
		parent::__construct($prefix);
		if (is_null(self::$cache)) {
			self::$cache = new \Memcached();

			$defaultOptions = [
				\Memcached::OPT_CONNECT_TIMEOUT => 50,
				\Memcached::OPT_RETRY_TIMEOUT => 50,
				\Memcached::OPT_SEND_TIMEOUT => 50,
				\Memcached::OPT_RECV_TIMEOUT => 50,
				\Memcached::OPT_POLL_TIMEOUT => 50,

				// Enable compression
				\Memcached::OPT_COMPRESSION => true,

				// Turn on consistent hashing
				\Memcached::OPT_LIBKETAMA_COMPATIBLE => true,

				// Enable Binary Protocol
				//\Memcached::OPT_BINARY_PROTOCOL =>      true,
			];
			/**
			 * By default enable igbinary serializer if available
			 *
			 * Psalm checks depend on if igbinary is installed or not with memcached
			 * @psalm-suppress RedundantCondition
			 * @psalm-suppress TypeDoesNotContainType
			 */
			if (\Memcached::HAVE_IGBINARY) {
				$defaultOptions[\Memcached::OPT_SERIALIZER] =
					\Memcached::SERIALIZER_IGBINARY;
			}
			$options = \OC::$server->getConfig()->getSystemValue('memcached_options', []);
			if (is_array($options)) {
				$options = $options + $defaultOptions;
				self::$cache->setOptions($options);
			} else {
				throw new HintException("Expected 'memcached_options' config to be an array, got $options");
			}

			$servers = \OC::$server->getSystemConfig()->getValue('memcached_servers');
			if (!$servers) {
				$server = \OC::$server->getSystemConfig()->getValue('memcached_server');
				if ($server) {
					$servers = [$server];
				} else {
					$servers = [['localhost', 11211]];
				}
			}
			self::$cache->addServers($servers);
		}
	}

	/**
	 * entries in XCache gets namespaced to prevent collisions between owncloud instances and users
	 */
	protected function getNameSpace() {
		return $this->prefix;
	}

	private function normalizeKey(string $key): string {
		// Memcached key has a max length of 250 characters and doesn't
		// allow spaces and control characters.
		if (version_compare(PHP_VERSION, '8.1.0') >= 0) {
			return hash('xxh128', $key); // Very fast and provide very good collisions protection
		} else {
			return hash('md5', $key);
		}
	}

	public function get($key) {
		$result = self::$cache->get($this->getNameSpace() . $this->normalizeKey($key));
		if ($result === false and self::$cache->getResultCode() == \Memcached::RES_NOTFOUND) {
			return null;
		} else {
			return $result;
		}
	}

	public function set($key, $value, $ttl = 0) {
		if ($ttl > 0) {
			$result = self::$cache->set($this->getNameSpace() . $this->normalizeKey($key), $value, $ttl);
		} else {
			$result = self::$cache->set($this->getNameSpace() . $this->normalizeKey($key), $value);
		}
		if ($result !== true) {
			$this->verifyReturnCode();
		}
		return $result;
	}

	public function hasKey($key) {
		self::$cache->get($this->getNameSpace() . $this->normalizeKey($key));
		return self::$cache->getResultCode() === \Memcached::RES_SUCCESS;
	}

	public function remove($key) {
		$result = self::$cache->delete($this->getNameSpace() . $this->normalizeKey($key));
		if (self::$cache->getResultCode() !== \Memcached::RES_NOTFOUND) {
			$this->verifyReturnCode();
		}
		return $result;
	}

	public function clear($prefix = '') {
		// Newer Memcached doesn't like getAllKeys(), flush everything
		self::$cache->flush();
		return true;
	}

	/**
	 * Set a value in the cache if it's not already stored
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl Time To Live in seconds. Defaults to 60*60*24
	 * @return bool
	 * @throws \Exception
	 */
	public function add($key, $value, $ttl = 0) {
		$result = self::$cache->add($this->getPrefix() . $this->normalizeKey($key), $value, $ttl);
		if (self::$cache->getResultCode() !== \Memcached::RES_NOTSTORED) {
			$this->verifyReturnCode();
		}
		return $result;
	}

	/**
	 * Increase a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	public function inc($key, $step = 1) {
		$this->add($this->normalizeKey($key), 0);
		$result = self::$cache->increment($this->getPrefix() . $this->normalizeKey($key), $step);

		if (self::$cache->getResultCode() !== \Memcached::RES_SUCCESS) {
			return false;
		}

		return $result;
	}

	/**
	 * Decrease a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	public function dec($key, $step = 1) {
		$result = self::$cache->decrement($this->getPrefix() . $this->normalizeKey($key), $step);

		if (self::$cache->getResultCode() !== \Memcached::RES_SUCCESS) {
			return false;
		}

		return $result;
	}

	public static function isAvailable(): bool {
		return extension_loaded('memcached');
	}

	/**
	 * @throws \Exception
	 */
	private function verifyReturnCode() {
		$code = self::$cache->getResultCode();
		if ($code === \Memcached::RES_SUCCESS) {
			return;
		}
		$message = self::$cache->getResultMessage();
		throw new \Exception("Error $code interacting with memcached : $message");
	}
}
