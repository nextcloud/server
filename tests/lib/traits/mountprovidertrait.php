<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Traits;

use OC\Files\Mount\MountPoint;
use OC\Files\Storage\StorageFactory;
use OCP\IUser;

/**
 * Allow setting mounts for users
 */
trait MountProviderTrait {
	/**
	 * @var \OCP\Files\Config\IMountProvider
	 */
	protected $mountProvider;

	/**
	 * @var \OC\Files\Storage\StorageFactory
	 */
	protected $storageFactory;

	protected $mounts = [];

	protected function registerMount($userId, $storage, $mountPoint, $arguments = null) {
		if (!isset($this->mounts[$userId])) {
			$this->mounts[$userId] = [];
		}
		$this->mounts[$userId][] = new MountPoint($storage, $mountPoint, $arguments, $this->storageFactory);
	}

	protected function registerStorageWrapper($name, $wrapper) {
		$this->storageFactory->addStorageWrapper($name, $wrapper);
	}

	protected function setUpMountProviderTrait() {
		$this->storageFactory = new StorageFactory();
		$this->mountProvider = $this->getMock('\OCP\Files\Config\IMountProvider');
		$this->mountProvider->expects($this->any())
			->method('getMountsForUser')
			->will($this->returnCallback(function (IUser $user) {
				if (isset($this->mounts[$user->getUID()])) {
					return $this->mounts[$user->getUID()];
				} else {
					return [];
				}
			}));
		\OC::$server->getMountProviderCollection()->registerProvider($this->mountProvider);
	}
}
