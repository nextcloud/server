<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_External;

use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;

/**
 * Person mount points can be moved by the user
 */
class PersonalMount extends MountPoint implements MoveableMount {
	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 * @return bool
	 */
	public function moveMount($target) {
		$result = \OC_Mount_Config::movePersonalMountPoint($this->getMountPoint(), $target, \OC_Mount_Config::MOUNT_TYPE_USER);
		$this->setMountPoint($target);
		return $result;
	}

	/**
	 * Remove the mount points
	 *
	 * @return bool
	 */
	public function removeMount() {
		$user = \OCP\User::getUser();
		$relativeMountPoint = substr($this->getMountPoint(), strlen('/' . $user . '/files/'));
		return \OC_Mount_Config::removeMountPoint($relativeMountPoint, \OC_Mount_Config::MOUNT_TYPE_USER, $user , true);
	}
}
