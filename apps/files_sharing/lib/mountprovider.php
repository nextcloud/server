<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Files_Sharing;

use OC\Files\Filesystem;
use OC\Files\Mount\MountPoint;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\IUser;

class MountProvider implements IMountProvider {
	/**
	 * @var \OCP\IConfig
	 */
	protected $config;

	/**
	 * @var IMountManager
	 */
	protected $mountManager;

	/**
	 * @param \OCP\IConfig $config
	 * @param IMountManager $mountManager
	 */
	public function __construct(IConfig $config, IMountManager $mountManager) {
		$this->config = $config;
		$this->mountManager = $mountManager;
	}


	/**
	 * Get all mountpoints applicable for the user and check for shares where we need to update the etags
	 *
	 * @param \OCP\IUser $user
	 * @param \OCP\Files\Storage\IStorageFactory $storageFactory
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function getMountsForUser(IUser $user, IStorageFactory $storageFactory) {
		$shares = \OCP\Share::getItemsSharedWithUser('file', $user->getUID());
		$shares = array_filter($shares, function ($share) {
			return $share['permissions'] > 0;
		});
		$shares = array_map(function ($share) use ($user, $storageFactory) {
			Filesystem::initMountPoints($share['uid_owner']);

			return new SharedMount(
				'\OC\Files\Storage\Shared',
				'/' . $user->getUID() . '/' . $share['file_target'],
				array(
					'share' => $share,
					'user' => $user->getUID()
				),
				$storageFactory
			);
		}, $shares);

		$subMounts = array_map(function (SharedMount $mountPoint) {
			$sourcePath = $mountPoint->getSourcePath();
			$sourceMounts = $this->mountManager->findIn($sourcePath);
			return array_map(function ($sourceMount) use ($mountPoint, $sourcePath) {
				return $this->copyMount($sourceMount, $mountPoint->getMountPoint(), $sourcePath);
			}, $sourceMounts);
		}, $shares);

		$subMounts = array_reduce($subMounts, function($allSubMounts, $shareSubMounts) {
			return array_merge($allSubMounts, $shareSubMounts);
		}, []);

		// array_filter removes the null values from the array
		return array_merge($shares, $subMounts);
	}

	/**
	 * @param IMountPoint $sourceMount
	 * @param string $shareTargetPath
	 * @param string $shareSourcePath
	 * @return IMountPoint
	 */
	private function copyMount(IMountPoint $sourceMount, $shareTargetPath, $shareSourcePath) {
		$subPath = substr($sourceMount->getMountPoint(), strlen($shareSourcePath) + 1);
		$targetMountPoint = $shareTargetPath . $subPath;
		return new MountPoint($sourceMount->getStorage(), $targetMountPoint, $sourceMount->getStorageArguments(), $sourceMount->getStorageFactory());
	}
}
