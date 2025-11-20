<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage;

use OC\Files\Cache\HomeCache;
use OC\Files\Cache\HomePropagator;
use OC\User\User;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\IPropagator;
use OCP\Files\IHomeStorage;
use OCP\Files\Storage\IStorage;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;

/**
 * Specialized version of Local storage for home directory usage
 */
class Home extends Local implements IHomeStorage {
	protected string $id;
	protected IUser $user;

	/**
	 * Construct a Home storage instance
	 *
	 * @param array $parameters array with "user" containing the
	 *                          storage owner
	 */
	public function __construct(array $parameters) {
		$this->user = $parameters['user'];
		$datadir = $this->user->getHome();
		$this->id = 'home::' . $this->user->getUID();

		parent::__construct(['datadir' => $datadir]);
	}

	public function getId(): string {
		return $this->id;
	}

	public function getCache(string $path = '', ?IStorage $storage = null): ICache {
		if (!$storage) {
			$storage = $this;
		}
		if (!isset($this->cache)) {
			$this->cache = new HomeCache($storage, $this->getCacheDependencies());
		}
		return $this->cache;
	}

	public function getPropagator(?IStorage $storage = null): IPropagator {
		if (!$storage) {
			$storage = $this;
		}
		if (!isset($this->propagator)) {
			$this->propagator = new HomePropagator($storage, Server::get(IDBConnection::class));
		}
		return $this->propagator;
	}


	public function getUser(): IUser {
		return $this->user;
	}

	public function getOwner(string $path): string|false {
		return $this->user->getUID();
	}
}
