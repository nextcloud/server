<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\User_LDAP;

use OCP\ICache;
use OCP\ICacheFactory;
use OCP\Server;

/**
 * @template T
 */
abstract class Proxy {
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

	protected function getAccess(string $configPrefix): Access {
		return $this->accessFactory->getAccessForPrefix($configPrefix);
	}

	protected function getUserCacheKey(string $uid): string {
		return 'user-' . $uid . '-lastSeenOn';
	}

	protected function getGroupCacheKey(string $gid): string {
		return 'group-' . $gid . '-lastSeenOn';
	}

	/**
	 * Asks the backend connected to the server that supposely takes care of the gid from the request.
	 *
	 * @param string $id the gid connected to the request
	 * @param string $method the method of the group backend that shall be called
	 * @param array $parameters an array of parameters to be passed
	 * @param bool $passOnWhen the result matches this variable
	 * @return mixed the result of the method or false
	 */
	abstract protected function callOnLastSeenOn(string $id, string $method, array $parameters, bool $passOnWhen): mixed;

	abstract protected function walkBackends(string $id, string $method, array $parameters): mixed;

	abstract public function getLDAPAccess(string $name): Access;

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
	 * @param string $method string, the method of the user backend that shall be called
	 * @param array $parameters an array of parameters to be passed
	 * @return mixed the result of the specified method
	 */
	protected function handleRequest(string $id, string $method, array $parameters, bool $passOnWhen = false): mixed {
		if (!$this->isSingleBackend()) {
			$result = $this->callOnLastSeenOn($id, $method, $parameters, $passOnWhen);
		}
		if (!isset($result) || $result === $passOnWhen) {
			$result = $this->walkBackends($id, $method, $parameters);
		}
		return $result;
	}

	private function getCacheKey(?string $key): string {
		$prefix = 'LDAP-Proxy-';
		if ($key === null) {
			return $prefix;
		}
		return $prefix . hash('sha256', $key);
	}

	/**
	 * @return mixed|null
	 */
	public function getFromCache(string $key) {
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

	public function writeToCache(string $key, mixed $value): void {
		if ($this->cache === null) {
			return;
		}
		$key = $this->getCacheKey($key);
		$value = base64_encode(json_encode($value));
		$this->cache->set($key, $value, 2592000);
	}

	public function clearCache(): void {
		if ($this->cache === null) {
			return;
		}
		$this->cache->clear($this->getCacheKey(null));
	}
}
