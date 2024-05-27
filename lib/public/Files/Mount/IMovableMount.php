<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Mount;

/**
 * Denotes that the mount point can be (re)moved by the user
 *
 * @since 28.0.0
 */
interface IMovableMount {
	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 * @return bool
	 * @since 28.0.0
	 */
	public function moveMount($target);

	/**
	 * Remove the mount points
	 *
	 * @return bool
	 * @since 28.0.0
	 */
	public function removeMount();
}
