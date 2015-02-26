<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Config;

use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;

class MountProviderCollection implements IMountProviderCollection {
	/**
	 * @var \OCP\Files\Config\IMountProvider[]
	 */
	private $providers = array();

	/**
	 * @var \OCP\Files\Storage\IStorageFactory
	 */
	private $loader;

	/**
	 * @param \OCP\Files\Storage\IStorageFactory $loader
	 */
	public function __construct(IStorageFactory $loader) {
		$this->loader = $loader;
	}

	/**
	 * Get all configured mount points for the user
	 *
	 * @param \OCP\IUser $user
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function getMountsForUser(IUser $user) {
		$loader = $this->loader;
		return array_reduce($this->providers, function ($mounts, IMountProvider $provider) use ($user, $loader) {
			return array_merge($mounts, $provider->getMountsForUser($user, $loader));
		}, array());
	}

	/**
	 * Add a provider for mount points
	 *
	 * @param \OCP\Files\Config\IMountProvider $provider
	 */
	public function registerProvider(IMountProvider $provider) {
		$this->providers[] = $provider;
	}
}
