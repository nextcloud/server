<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Actions;

/**
 * Class Auth logs all auth related actions
 *
 * @package OCA\AdminAudit\Actions
 */
class Auth extends Action {
	public function loginAttempt(array $params): void {
		$this->log(
			'Login attempt: "%s"',
			$params,
			[
				'uid',
			],
			true
		);
	}

	public function loginSuccessful(array $params): void {
		$this->log(
			'Login successful: "%s"',
			$params,
			[
				'uid',
			],
			true
		);
	}

	public function logout(array $params): void {
		$this->log(
			'Logout occurred',
			[],
			[]
		);
	}
}
