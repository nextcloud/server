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
		$this->mounts[$userId][] = ['storage' => $storage, 'mountPoint' => $mountPoint, 'arguments' => $arguments];
	}

	protected function registerStorageWrapper($name, $wrapper) {
		$this->storageFactory->addStorageWrapper($name, $wrapper);
	}

	protected function setUpMountProviderTrait() {
		$this->storageFactory = new StorageFactory();
		$this->mountProvider = $this->getMockBuilder('\OCP\Files\Config\IMountProvider')->getMock();
		$this->mountProvider->expects($this->any())
			->method('getMountsForUser')
			->will($this->returnCallback(function (IUser $user) {
				if (isset($this->mounts[$user->getUID()])) {
					return array_map(function ($config) {
						return new MountPoint($config['storage'], $config['mountPoint'], $config['arguments'], $this->storageFactory);
					}, $this->mounts[$user->getUID()]);
				} else {
					return [];
				}
			}));
		\OC::$server->getMountProviderCollection()->registerProvider($this->mountProvider);
	}
}
