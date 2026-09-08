<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\User;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Config\IUserConfig;
use OCP\IUser;

/**
 * Timestamp of the last time an account actually authenticated.
 *
 * Distinct from {@see IUser::getLastLogin()}, which is also refreshed while
 * revalidating an already authenticated session and therefore keeps moving for
 * any account that merely has a session open somewhere. Verification tokens
 * expire on login, so they must be compared against this value instead.
 */
class LastInteractiveLogin {
	private const string CONFIG_APP = 'login';
	private const string CONFIG_KEY = 'lastInteractiveLogin';

	public function __construct(
		private IUserConfig $userConfig,
		private ITimeFactory $timeFactory,
	) {
	}

	public function record(IUser $user): void {
		$this->userConfig->setValueInt(
			$user->getUID(),
			self::CONFIG_APP,
			self::CONFIG_KEY,
			$this->timeFactory->getTime(),
		);
	}

	public function get(IUser $user): int {
		return $this->userConfig->getValueInt(
			$user->getUID(),
			self::CONFIG_APP,
			self::CONFIG_KEY,
		);
	}
}
