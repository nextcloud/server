<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
