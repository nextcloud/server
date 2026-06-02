<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Mount;

use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;

class HomeMountPoint extends MountPoint {
	public function __construct(
		private IUser $user,
		$storage,
		string $mountpoint,
		?array $arguments = null,
		?IStorageFactory $loader = null,
		?array $mountOptions = null,
		?int $mountId = null,
		?string $mountProvider = null,
	) {
		parent::__construct($storage, $mountpoint, $arguments, $loader, $mountOptions, $mountId, $mountProvider);
	}

	public function getUser(): IUser {
		return $this->user;
	}
}
