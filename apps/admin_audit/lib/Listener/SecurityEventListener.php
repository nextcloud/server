<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengeFailed;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<TwoFactorProviderChallengePassed|TwoFactorProviderChallengeFailed>
 */
class SecurityEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof TwoFactorProviderChallengePassed) {
			$this->twoFactorProviderChallengePassed($event);
		} elseif ($event instanceof TwoFactorProviderChallengeFailed) {
			$this->twoFactorProviderChallengeFailed($event);
		}
	}

	private function twoFactorProviderChallengePassed(TwoFactorProviderChallengePassed $event): void {
		$this->log(
			'Successful two factor attempt by user %s (%s) with provider %s',
			[
				'uid' => $event->getUser()->getUID(),
				'displayName' => $event->getUser()->getDisplayName(),
				'provider' => $event->getProvider()->getDisplayName()
			],
			[
				'displayName',
				'uid',
				'provider',
			]
		);
	}

	private function twoFactorProviderChallengeFailed(TwoFactorProviderChallengeFailed $event): void {
		$this->log(
			'Failed two factor attempt by user %s (%s) with provider %s',
			[
				'uid' => $event->getUser()->getUID(),
				'displayName' => $event->getUser()->getDisplayName(),
				'provider' => $event->getProvider()->getDisplayName()
			],
			[
				'displayName',
				'uid',
				'provider',
			]
		);
	}
}
