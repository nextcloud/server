<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

/**
 * This class provides avatar functionality
 * @since 6.0.0
 */

interface IAvatarManager {
	/**
	 * Return a user specific instance of \OCP\IAvatar
	 * @see IAvatar
	 * @param string $userId the Nextcloud user id
	 * @throws \Exception In case the username is potentially dangerous
	 * @throws \OCP\Files\NotFoundException In case there is no user folder yet
	 * @since 6.0.0
	 */
	public function getAvatar(string $userId): IAvatar;

	/**
	 * Returns a guest user avatar instance.
	 *
	 * @param string $name The guest name, e.g. "Albert".
	 * @return IAvatar
	 * @since 16.0.0
	 */
	public function getGuestAvatar(string $name): IAvatar;
}
