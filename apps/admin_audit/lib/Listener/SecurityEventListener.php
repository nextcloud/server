<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
