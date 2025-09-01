<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Listener;

use OCP\Accounts\IAccountManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\VerificationToken\IVerificationToken;
use OCP\User\Events\PasswordUpdatedEvent;

/**
 * @template-implements IEventListener<PasswordUpdatedEvent>
 */
class PasswordUpdatedListener implements IEventListener {
	public function __construct(
		readonly private IVerificationToken $verificationToken,
		readonly private IAccountManager $accountManager,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof PasswordUpdatedEvent) {
			// Delete lost password tokens
			$this->verificationToken->delete('', $event->getUser(), 'lostpassword');

			// Delete email verification token
			$account = $this->accountManager->getAccount($event->getUser());
			$this->accountManager->invalidEmailChangeVerificationRequests($account);
		}
	}
}
