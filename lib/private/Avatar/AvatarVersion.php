<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Avatar;

use OCP\Config\IUserConfig;

/**
 * The counter that invalidates a cached avatar URL.
 *
 * Anything that changes what a viewer would see has to bump it, or they keep
 * the old picture for the full cache window.
 */
class AvatarVersion {
	public function __construct(
		private IUserConfig $userConfig,
	) {
	}

	public function bump(string $userId): void {
		$this->userConfig->setValueInt(
			$userId,
			'avatar',
			'version',
			$this->userConfig->getValueInt($userId, 'avatar', 'version') + 1,
		);
	}
}
