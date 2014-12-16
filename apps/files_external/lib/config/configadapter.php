<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_External\Config;

use OC\Files\Mount\MountPoint;
use OCP\Files\Storage\IStorageFactory;
use OCA\Files_External\PersonalMount;
use OCP\Files\Config\IMountProvider;
use OCP\IUser;

/**
 * Make the old files_external config work with the new public mount config api
 */
class ConfigAdapter implements IMountProvider {
	/**
	 * Get all mountpoints applicable for the user
	 *
	 * @param \OCP\IUser $user
	 * @param \OCP\Files\Storage\IStorageFactory $loader
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function getMountsForUser(IUser $user, IStorageFactory $loader) {
		$mountPoints = \OC_Mount_Config::getAbsoluteMountPoints($user->getUID());
		$mounts = array();
		foreach ($mountPoints as $mountPoint => $options) {
			if (isset($options['options']['objectstore'])) {
				$objectClass = $options['options']['objectstore']['class'];
				$options['options']['objectstore'] = new $objectClass($options['options']['objectstore']);
			}
			$mountOptions = isset($options['mountOptions']) ? $options['mountOptions'] : [];
			if (isset($options['personal']) && $options['personal']) {
				$mounts[] = new PersonalMount($options['class'], $mountPoint, $options['options'], $loader, $mountOptions);
			} else {
				$mounts[] = new MountPoint($options['class'], $mountPoint, $options['options'], $loader, $mountOptions);
			}
		}
		return $mounts;
	}
}
