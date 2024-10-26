<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Files;

use OCP\Files\Storage\IStorage;
use OCP\IUser;

/**
 * Interface IHomeStorage
 *
 * @since 7.0.0
 */
interface IHomeStorage extends IStorage {
	/**
	 * Get the user for this home storage
	 *
	 * @return IUser
	 * @since 28.0.0
	 */
	public function getUser(): IUser;
}
