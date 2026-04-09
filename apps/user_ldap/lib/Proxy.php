<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP;

use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\Server;

/**
 * @template T
 */
abstract class Proxy {
	/** @var array<string,Access> */
	private static array $accesses = [];
	private ?bool $isSingleBackend = null;
	private ?ICache $cache = null;

	/** @var T[] */
	protected array $backends = [];
	/** @var ?T */
	protected $refBackend = null;

	protected bool $isSetUp = false;

	public function __construct(
		private Helper $helper,
		private ILDAPWrapper $ldap,
		private AccessFactory $accessFactory,
	) {
		$memcache = Server::get(ICacheFactory::class);
		if ($memcache->isAvailable()) {
			$this->cache = $memcache->createDistributed();
		}
	}

	protected function setup(): void {
		if ($this->isSetUp) {
			return;
		}

		$serverConfigPrefixes = $this->helper->getServerConfigurationPrefixes(true);
		foreach ($serverConfigPrefixes as $configPrefix) {
			$this->backends[$configPrefix] = $this->newInstance($configPrefix);

			if (is_null($this->refBackend)) {
				$this->refBackend = $this->backends[$configPrefix];
			}
		}

		$this->isSetUp = true;
	}

	/**
	 * @return T
	 */
	abstract protected function newInstance(string $configPrefix): object;

	/**
	 * @return T
	 */
	public function getBackend(string $configPrefix): object {
		$this->setup();
		return $this->backends[$configPrefix];
	}

	private function addAccess(string $configPrefix): void {
		$userMap = Server::get(UserMapping::class);
		$groupMap = Server::get(GroupMapping::class);

		$connector = new Connection($this->ldap, $configPrefix);
		$access = $this->accessFactory->get($connector);
		$access->setUserMapper($userMap);
		$access->setGroupMapper($groupMap);
		self::$accesses[$configPrefix] = $access;
	}

	protected function getAccess(string $configPrefix): Access {
		if (!isset(self::$accesses[$configPrefix])) {
			$this->addAccess($configPrefix);
		}
		return self::$accesses[$configPrefix];
	}

	/**
	 * @param string $uid
	 * @return string
	 */
	protected function getUserCacheKey($uid) {
		return 'user-' . $uid . '-lastSeenOn';
	}

	/**
	 * @param string $gid
	 * @return string
	 */
	protected function getGroupCacheKey($gid) {
		return 'group-' . $gid . '-lastSeenOn';
	}

	/**
	 * @param string $id
	 * @param string $method
	 * @param array $parameters
	 * @param bool $passOnWhen
	 * @return mixed
	 */
	abstract protected function callOnLastSeenOn($id, $method, $parameters, $passOnWhen);

	/**
	 * @param string $id
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 */
	abstract protected function walkBackends($id, $method, $parameters);

	/**
	 * @param string $id
	 * @return Access
	 */
	abstract public function getLDAPAccess($id);

	abstract protected function activeBackends(): int;

	protected function isSingleBackend(): bool {
		if ($this->isSingleBackend === null) {
			$this->isSingleBackend = $this->activeBackends() === 1;
		}
		return $this->isSingleBackend;
	}

	/**
	 * Takes care of the request to the User backend
	 *
	 * @param string $id
	 * @param string $method string, the method of the user backend that shall be called
	 * @param array $parameters an array of parameters to be passed
	 * @param bool $passOnWhen
	 * @return mixed the result of the specified method
	 */
	protected function handleRequest($id, $method, $parameters, $passOnWhen = false) {
		if (!$this->isSingleBackend()) {
			$result = $this->callOnLastSeenOn($id, $method, $parameters, $passOnWhen);
		}
		if (!isset($result) || $result === $passOnWhen) {
			$result = $this->walkBackends($id, $method, $parameters);
		}
		return $result;
	}

	/**
	 * @param string|null $key
	 * @return string
	 */
	private function getCacheKey($key) {
		$prefix = 'LDAP-Proxy-';
		if ($key === null) {
			return $prefix;
		}
		return $prefix . hash('sha256', $key);
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function getFromCache($key) {
		if ($this->cache === null) {
			return null;
		}

		$key = $this->getCacheKey($key);
		$value = $this->cache->get($key);
		if ($value === null) {
			return null;
		}

		return json_decode(base64_decode($value));
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function writeToCache($key, $value) {
		if ($this->cache === null) {
			return;
		}
		$key = $this->getCacheKey($key);
		$value = base64_encode(json_encode($value));
		$this->cache->set($key, $value, 2592000);
	}

	public function clearCache() {
		if ($this->cache === null) {
			return;
		}
		$this->cache->clear($this->getCacheKey(null));
	}
}
