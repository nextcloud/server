<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Notify;

/**
 * Represents a detected rename change
 *
 * @since 12.0.0
 */
interface IRenameChange extends IChange {
	/**
	 * Get the new path of the renamed file relative to the storage root
	 *
	 * @return string
	 *
	 * @since 12.0.0
	 */
	public function getTargetPath();
}
