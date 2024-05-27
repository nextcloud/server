<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Actions;

use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IUser;

/**
 * Class Sharing logs the sharing actions
 *
 * @package OCA\AdminAudit\Actions
 */
class Security extends Action {
	/**
	 * Logs failed twofactor challenge
	 */
	public function twofactorFailed(IUser $user, IProvider $provider): void {
		$params = [
			'displayName' => $user->getDisplayName(),
			'uid' => $user->getUID(),
			'provider' => $provider->getDisplayName(),
		];

		$this->log(
			'Failed two factor attempt by user %s (%s) with provider %s',
			$params,
			[
				'displayName',
				'uid',
				'provider',
			]
		);
	}

	/**
	 * Logs successful twofactor challenge
	 */
	public function twofactorSuccess(IUser $user, IProvider $provider): void {
		$params = [
			'displayName' => $user->getDisplayName(),
			'uid' => $user->getUID(),
			'provider' => $provider->getDisplayName(),
		];

		$this->log(
			'Successful two factor attempt by user %s (%s) with provider %s',
			$params,
			[
				'displayName',
				'uid',
				'provider',
			]
		);
	}
}
