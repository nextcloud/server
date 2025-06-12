<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Traits;

use OC\Files\Mount\MountPoint;
use OC\Files\Storage\StorageFactory;
use OCP\Files\Config\IMountProviderCollection;
use OCP\IUser;
use OCP\Server;

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

		if ($this->IsDatabaseAccessAllowed()) {
			$mount = new MountPoint($storage, $mountPoint, $arguments, $this->storageFactory);
			$storage = $mount->getStorage();
			$storage->getScanner()->scan('');
		}
	}

	protected function registerStorageWrapper($name, $wrapper) {
		$this->storageFactory->addStorageWrapper($name, $wrapper);
	}

	protected function setUpMountProviderTrait() {
		$this->storageFactory = new StorageFactory();
		$this->mountProvider = $this->getMockBuilder('\OCP\Files\Config\IMountProvider')->getMock();
		$this->mountProvider->expects($this->any())
			->method('getMountsForUser')
			->willReturnCallback(function (IUser $user) {
				if (isset($this->mounts[$user->getUID()])) {
					return array_map(function ($config) {
						return new MountPoint($config['storage'], $config['mountPoint'], $config['arguments'], $this->storageFactory);
					}, $this->mounts[$user->getUID()]);
				} else {
					return [];
				}
			});
		Server::get(IMountProviderCollection::class)->registerProvider($this->mountProvider);
	}
}
