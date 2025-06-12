<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test;

use OC\Files\Mount;
use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;

/**
 * Test moveable mount for mocking
 */
class TestMoveableMountPoint extends MountPoint implements MoveableMount {
	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 * @return bool
	 */
	public function moveMount($target) {
		$this->setMountPoint($target);
	}

	/**
	 * Remove the mount points
	 *
	 * @return mixed
	 * @return bool
	 */
	public function removeMount() {
	}
}
