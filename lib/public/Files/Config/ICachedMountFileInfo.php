<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Config;

/**
 * Holds information about a mount for a user
 *
 * @since 13.0.0
 */
interface ICachedMountFileInfo extends ICachedMountInfo {
	/**
	 * Return the path for the file within the cached mount
	 *
	 * @return string
	 * @since 13.0.0
	 */
	public function getInternalPath(): string;

	/**
	 * @return string
	 * @since 13.0.0
	 */
	public function getPath(): string;
}
