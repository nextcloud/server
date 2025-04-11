<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Mount;

use OCP\Files\Mount\IMovableMount;

/**
 * Defines the mount point to be (re)moved by the user
 */
interface MoveableMount extends IMovableMount {
	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 * @return bool
	 */
	public function moveMount($target);

	/**
	 * Remove the mount points
	 *
	 * @return bool
	 */
	public function removeMount();
}
