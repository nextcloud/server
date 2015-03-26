<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
