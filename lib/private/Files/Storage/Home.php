<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage;

use OC\Files\Cache\HomePropagator;
use OCP\IUser;

/**
 * Specialized version of Local storage for home directory usage
 */
class Home extends Local implements \OCP\Files\IHomeStorage {
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var \OC\User\User $user
	 */
	protected $user;

	public function __construct($arguments) {
		$this->user = $arguments['user'];
		$datadir = $this->user->getHome();
		$this->id = 'home::' . $this->user->getUID();

		parent::__construct(['datadir' => $datadir]);
	}

	public function getId() {
		return $this->id;
	}

	public function getCache($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		if (!isset($this->cache)) {
			$this->cache = new \OC\Files\Cache\HomeCache($storage, $this->getCacheDependencies());
		}
		return $this->cache;
	}

	public function getPropagator($storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		if (!isset($this->propagator)) {
			$this->propagator = new HomePropagator($storage, \OC::$server->getDatabaseConnection());
		}
		return $this->propagator;
	}


	public function getUser(): IUser {
		return $this->user;
	}

	public function getOwner($path) {
		return $this->user->getUID();
	}
}
