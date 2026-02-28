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
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserRegistered;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserUnregistered;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<TwoFactorProviderChallengePassed|TwoFactorProviderChallengeFailed|TwoFactorProviderForUserRegistered|TwoFactorProviderForUserUnregistered>
 */
class SecurityEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof TwoFactorProviderChallengePassed) {
			$this->twoFactorProviderChallengePassed($event);
		} elseif ($event instanceof TwoFactorProviderChallengeFailed) {
			$this->twoFactorProviderChallengeFailed($event);
		} elseif ($event instanceof TwoFactorProviderForUserRegistered) {
			$this->twoFactorProviderForUserRegistered($event);
		} elseif ($event instanceof TwoFactorProviderForUserUnregistered) {
			$this->twoFactorProviderForUserUnregistered($event);
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

	private function twoFactorProviderForUserRegistered(TwoFactorProviderForUserRegistered $event): void {
		$this->log(
			'Two factor provider %s enabled for user %s (%s)',
			[
				'provider' => $event->getProvider()->getDisplayName(),
				'uid' => $event->getUser()->getUID(),
				'displayName' => $event->getUser()->getDisplayName()
			],
			[
				'provider',
				'uid',
				'displayName',
			]
		);
	}

	private function twoFactorProviderForUserUnregistered(TwoFactorProviderForUserUnregistered $event): void {
		$this->log(
			'Two factor provider %s disabled for user %s (%s)',
			[
				'provider' => $event->getProvider()->getDisplayName(),
				'uid' => $event->getUser()->getUID(),
				'displayName' => $event->getUser()->getDisplayName()
			],
			[
				'provider',
				'uid',
				'displayName',
			]
		);
	}
}
